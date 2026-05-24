<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Events\PaymentCompleted;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentService
{
    public function __construct(
        protected SappayService $sappayService
    ) {}

    /**
     * Initiate a payment for an order
     */
    public function initiatePayment(
        Order $order,
        User $user,
        PaymentMethod $method,
        string $phoneNumber
    ): array {
        // Security: Verify order ownership
        if ($order->client_id !== $user->id) {
            Log::warning('Payment attempt on non-owned order', [
                'user_id' => $user->id,
                'order_id' => $order->id,
                'order_client_id' => $order->client_id,
            ]);
            return [
                'success' => false,
                'message' => 'Accès non autorisé à cette commande.',
            ];
        }

        // Security: Check if payment already exists and is successful
        if ($order->payment && $order->payment->isSuccess()) {
            Log::info('Double payment attempt blocked', [
                'user_id' => $user->id,
                'order_id' => $order->id,
                'existing_payment_id' => $order->payment->id,
            ]);
            return [
                'success' => false,
                'message' => 'Cette commande a déjà été payée.',
            ];
        }

        // Security: Check order status (prevent payment on cancelled/delivered orders)
        if (!in_array($order->status->value, ['pending', 'assigned'])) {
            Log::warning('Payment attempt on invalid order status', [
                'user_id' => $user->id,
                'order_id' => $order->id,
                'order_status' => $order->status->value,
            ]);
            return [
                'success' => false,
                'message' => 'Cette commande ne peut plus être payée.',
            ];
        }

        // Use database transaction with pessimistic locking
        return DB::transaction(function () use ($order, $user, $method, $phoneNumber) {
            // Lock the order row to prevent race conditions
            $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->first();

            // Double-check after lock
            if ($lockedOrder->payment && $lockedOrder->payment->isSuccess()) {
                return [
                    'success' => false,
                    'message' => 'Cette commande a déjà été payée.',
                ];
            }

            // Create or update payment record
            $payment = Payment::updateOrCreate(
                ['order_id' => $lockedOrder->id],
                [
                    'user_id' => $user->id,
                    'amount' => $lockedOrder->total_price,
                    'method' => $method,
                    'status' => PaymentStatus::PENDING,
                    'phone_number' => $phoneNumber,
                ]
            );

            Log::info('Payment initiated', [
                'payment_id' => $payment->id,
                'transaction_id' => $payment->transaction_id,
                'user_id' => $user->id,
                'order_id' => $lockedOrder->id,
                'amount' => $payment->amount,
                'method' => $method->value,
                'phone' => substr($phoneNumber, 0, 5) . '****',
            ]);

            // Les paiements en espèces ne passent pas par Sappay
            if ($method === PaymentMethod::CASH) {
                return [
                    'success' => true,
                    'message' => 'Commande enregistrée. Paiement en espèces à la livraison.',
                    'payment' => $payment->fresh(),
                    'cash' => true,
                ];
            }

            // Le paiement via Sappay se fait via SappayController::initiateOrderPayment
            // PaymentService gère uniquement les paiements cash et la validation interne
            return [
                'success' => true,
                'message' => 'Paiement initié. Utilisez /api/v1/sappay/pay-order pour finaliser via Sappay.',
                'payment' => $payment->fresh(),
            ];
        });
    }

    /**
     * Handle successful payment
     */
    private function handleSuccess(Payment $payment, string $providerTransactionId): array
    {
        $payment->markAsSuccess($providerTransactionId, json_encode([
            'status' => 'SUCCESS',
            'transaction_id' => $providerTransactionId,
            'timestamp' => now()->toIso8601String(),
        ]));

        Log::info('Payment completed successfully', [
            'payment_id' => $payment->id,
            'transaction_id' => $payment->transaction_id,
            'provider_transaction_id' => $providerTransactionId,
            'order_id' => $payment->order_id,
        ]);

        // Dispatch event for push notification
        event(new PaymentCompleted($payment, $payment->order));

        return [
            'success' => true,
            'message' => 'Paiement effectué avec succès.',
            'payment' => $payment->fresh(),
        ];
    }

    /**
     * Handle pending payment
     */
    private function handlePending(Payment $payment, string $providerTransactionId): array
    {
        $payment->update([
            'provider_transaction_id' => $providerTransactionId,
            'provider_response' => json_encode([
                'status' => 'PENDING',
                'transaction_id' => $providerTransactionId,
                'message' => 'En attente de validation par l\'utilisateur',
                'timestamp' => now()->toIso8601String(),
            ]),
        ]);

        Log::info('Payment pending user validation', [
            'payment_id' => $payment->id,
            'transaction_id' => $payment->transaction_id,
        ]);

        return [
            'success' => true,
            'pending' => true,
            'message' => 'Veuillez valider le paiement sur votre téléphone.',
            'payment' => $payment->fresh(),
        ];
    }

    /**
     * Handle failed payment
     */
    private function handleFailure(Payment $payment, string $reason): array
    {
        $payment->markAsFailed($reason, json_encode([
            'status' => 'FAILED',
            'reason' => $reason,
            'timestamp' => now()->toIso8601String(),
        ]));

        Log::warning('Payment failed', [
            'payment_id' => $payment->id,
            'transaction_id' => $payment->transaction_id,
            'reason' => $reason,
        ]);

        return [
            'success' => false,
            'message' => "Échec du paiement: {$reason}",
            'payment' => $payment->fresh(),
        ];
    }

    /**
     * Check payment status via Sappay
     */
    public function checkStatus(Payment $payment): array
    {
        if ($payment->isSuccess()) {
            return [
                'success' => true,
                'status' => 'success',
                'message' => 'Paiement confirmé.',
                'payment' => $payment,
            ];
        }

        if ($payment->isFailed()) {
            return [
                'success' => false,
                'status' => 'failed',
                'message' => $payment->failure_reason ?? 'Paiement échoué.',
                'payment' => $payment,
            ];
        }

        // Pour les paiements en attente, le statut réel est dans sappay_transactions
        if ($payment->provider_transaction_id) {
            $sappayTx = \App\Models\SappayTransaction::where('invoice_id', $payment->provider_transaction_id)->first();

            if ($sappayTx && $sappayTx->isSuccess()) {
                $payment->markAsSuccess(
                    $payment->provider_transaction_id,
                    json_encode(['source' => 'sappay', 'invoice_id' => $sappayTx->invoice_id])
                );

                if ($payment->order) {
                    event(new PaymentCompleted($payment, $payment->order));
                }

                return [
                    'success' => true,
                    'status'  => 'success',
                    'message' => 'Paiement confirmé.',
                    'payment' => $payment->fresh(),
                ];
            }

            if ($sappayTx && $sappayTx->isFailed()) {
                $payment->markAsFailed('Paiement échoué via Sappay');
                return [
                    'success' => false,
                    'status'  => 'failed',
                    'message' => 'Le paiement a échoué.',
                    'payment' => $payment->fresh(),
                ];
            }
        }

        return [
            'success' => true,
            'status' => 'pending',
            'message' => 'Paiement en attente de validation.',
            'payment' => $payment,
        ];
    }

    /**
     * Handle webhook callback from payment provider
     */
    public function handleWebhook(array $data): array
    {
        Log::info('Payment webhook received', [
            'data' => array_merge($data, ['signature' => '***REDACTED***']),
        ]);

        // In production: validate webhook signature
        // $this->validateWebhookSignature($data);

        $transactionId = $data['transaction_id'] ?? null;

        if (!$transactionId) {
            Log::warning('Webhook missing transaction_id', ['data' => $data]);
            return [
                'success' => false,
                'message' => 'Transaction ID manquant.',
            ];
        }

        $payment = Payment::where('transaction_id', $transactionId)
            ->orWhere('provider_transaction_id', $transactionId)
            ->first();

        if (!$payment) {
            Log::warning('Webhook for unknown payment', ['transaction_id' => $transactionId]);
            return [
                'success' => false,
                'message' => 'Paiement non trouvé.',
            ];
        }

        $status = $data['status'] ?? 'unknown';

        Log::info('Processing webhook', [
            'payment_id' => $payment->id,
            'status' => $status,
        ]);

        match ($status) {
            'SUCCESS', 'SUCCESSFUL' => $payment->markAsSuccess(
                $data['provider_transaction_id'] ?? $transactionId,
                json_encode($data)
            ),
            'FAILED', 'FAILURE' => $payment->markAsFailed(
                $data['reason'] ?? 'Échec du paiement',
                json_encode($data)
            ),
            default => Log::warning('Unknown webhook status', ['status' => $status]),
        };

        return [
            'success' => true,
            'message' => 'Webhook traité.',
            'payment' => $payment->fresh(),
        ];
    }

    /**
     * Verify webhook signature
     */
    public function verifyWebhookSignature(string $payload, ?string $signature): bool
    {
        $secret = config('services.payment.webhook_secret');

        if (empty($secret)) {
            Log::channel('security')->error('Payment: Webhook secret not configured');
            return false;
        }

        if (empty($signature)) {
            Log::channel('security')->warning('Payment: No signature provided');
            return false;
        }

        $expected = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expected, $signature);
    }

    /**
     * Get payment history for a user
     */
    public function getUserPayments(User $user, int $perPage = 15)
    {
        return Payment::with('order:id,order_number')
            ->where('user_id', $user->id)
            ->latest()
            ->paginate($perPage);
    }
}
