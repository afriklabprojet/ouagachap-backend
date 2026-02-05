<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\JekoTransaction;
use App\Models\Wallet;
use App\Services\JekoPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class JekoWebhookController extends Controller
{
    public function __construct(
        private JekoPaymentService $jekoService
    ) {}

    /**
     * Gérer le webhook JEKO
     * 
     * La signature HMAC-SHA256 est vérifiée avec le corps brut de la requête
     */
    public function handle(Request $request): JsonResponse
    {
        // IMPORTANT: Utiliser le corps brut pour la vérification HMAC
        $rawPayload = $request->getContent();
        $payload = $request->all();
        $signature = $request->header('Jeko-Signature') ?? $request->header('X-Jeko-Signature') ?? '';

        Log::channel('security')->info('Webhook JEKO reçu', [
            'ip' => $request->ip(),
            'has_signature' => !empty($signature),
            'user_agent' => $request->userAgent(),
        ]);

        // Vérifier la signature HMAC-SHA256 avec le corps brut
        if (!$this->jekoService->verifyWebhookSignature($rawPayload, $signature)) {
            Log::channel('security')->warning('Signature webhook JEKO invalide', [
                'ip' => $request->ip(),
                'signature_received' => substr($signature, 0, 20) . '...',
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Signature invalide',
            ], 401);
        }

        // Récupérer la référence de la transaction
        $reference = $payload['transactionDetails']['reference'] ?? null;
        
        if (!$reference) {
            Log::warning('Webhook JEKO sans référence', $payload);
            return response()->json([
                'success' => false,
                'message' => 'Référence manquante',
            ], 400);
        }

        // Trouver la transaction locale
        $transaction = JekoTransaction::where('reference', $reference)->first();

        if (!$transaction) {
            Log::warning('Transaction JEKO non trouvée', [
                'reference' => $reference,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Transaction non trouvée',
            ], 404);
        }

        // Ne pas retraiter les transactions déjà terminées
        if (!$transaction->isPending()) {
            Log::info('Transaction JEKO déjà traitée', [
                'reference' => $reference,
                'status' => $transaction->status,
            ]);
            return response()->json([
                'success' => true,
                'message' => 'Transaction déjà traitée',
            ]);
        }

        try {
            DB::transaction(function () use ($transaction, $payload) {
                $status = $payload['status'] ?? null;
                
                if ($status === 'success') {
                    $this->processSuccessfulPayment($transaction, $payload);
                } elseif (in_array($status, ['error', 'failed', 'expired', 'cancelled'])) {
                    $transaction->markAsError($payload['message'] ?? 'Paiement échoué');
                }
            });

            Log::info('Webhook JEKO traité avec succès', [
                'reference' => $reference,
                'status' => $payload['status'] ?? 'unknown',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Webhook traité avec succès',
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur traitement webhook JEKO', [
                'reference' => $reference,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur de traitement',
            ], 500);
        }
    }

    /**
     * Traiter un paiement réussi depuis webhook
     */
    private function processSuccessfulPayment(JekoTransaction $transaction, array $payload): void
    {
        // Mettre à jour la transaction
        $transaction->update([
            'status' => 'success',
            'jeko_transaction_id' => $payload['id'] ?? null,
            'fees' => isset($payload['fees']) ? $payload['fees'] / 100 : 0, // Convertir centimes en FCFA
            'counterpart_label' => $payload['counterpartLabel'] ?? null,
            'counterpart_identifier' => $payload['counterpartIdentifier'] ?? null,
            'payment_method' => $payload['paymentMethod'] ?? $transaction->payment_method,
            'webhook_payload' => $payload,
            'executed_at' => now(),
        ]);

        // Traiter selon le type de transaction
        if ($transaction->type === 'wallet_recharge') {
            $this->processWalletRecharge($transaction);
        } elseif ($transaction->type === 'order_payment') {
            $this->processOrderPayment($transaction);
        }

        // Notifier l'utilisateur
        $this->notifyUser($transaction);
    }

    /**
     * Traiter une recharge de wallet
     */
    private function processWalletRecharge(JekoTransaction $transaction): void
    {
        $wallet = Wallet::firstOrCreate(
            ['user_id' => $transaction->user_id],
            ['balance' => 0]
        );

        // Créditer le wallet
        $wallet->increment('balance', $transaction->amount);

        // Enregistrer la transaction wallet
        $wallet->transactions()->create([
            'type' => 'deposit',
            'amount' => $transaction->amount,
            'balance_after' => $wallet->balance,
            'description' => "Recharge via {$transaction->payment_method_name}",
            'reference' => $transaction->reference,
            'metadata' => [
                'jeko_transaction_id' => $transaction->id,
                'payment_method' => $transaction->payment_method,
                'fees' => $transaction->fees,
            ],
        ]);

        Log::info('Wallet rechargé via webhook JEKO', [
            'user_id' => $transaction->user_id,
            'amount' => $transaction->amount,
            'new_balance' => $wallet->fresh()->balance,
        ]);
    }

    /**
     * Traiter un paiement de commande
     */
    private function processOrderPayment(JekoTransaction $transaction): void
    {
        $orderId = $transaction->metadata['order_id'] ?? null;
        
        if (!$orderId) {
            Log::error('Order ID manquant dans la transaction JEKO (webhook)', [
                'transaction_id' => $transaction->id,
            ]);
            return;
        }

        $order = \App\Models\Order::find($orderId);
        
        if (!$order) {
            Log::error('Commande non trouvée pour paiement JEKO (webhook)', [
                'order_id' => $orderId,
                'transaction_id' => $transaction->id,
            ]);
            return;
        }

        // Mettre à jour le statut de paiement de la commande
        $order->update([
            'payment_status' => 'paid',
            'payment_method' => "jeko_{$transaction->payment_method}",
            'paid_at' => now(),
        ]);

        Log::info('Commande payée via webhook JEKO', [
            'order_id' => $order->id,
            'tracking_number' => $order->tracking_number,
            'amount' => $transaction->amount,
            'payment_method' => $transaction->payment_method,
        ]);
    }

    /**
     * Notifier l'utilisateur du résultat du paiement
     */
    private function notifyUser(JekoTransaction $transaction): void
    {
        $user = $transaction->user;
        
        if (!$user || !$user->fcm_token) {
            return;
        }

        try {
            $title = $transaction->isSuccessful() ? '✅ Paiement réussi' : '❌ Paiement échoué';
            
            $body = match ($transaction->type) {
                'wallet_recharge' => $transaction->isSuccessful() 
                    ? "Votre compte a été crédité de {$transaction->formatted_amount}"
                    : "La recharge de {$transaction->formatted_amount} a échoué",
                'order_payment' => $transaction->isSuccessful()
                    ? "Paiement de {$transaction->formatted_amount} effectué avec succès"
                    : "Le paiement de {$transaction->formatted_amount} a échoué",
                default => "Transaction {$transaction->reference} - {$transaction->status_label}",
            };

            // Envoyer notification FCM (si service disponible)
            if (class_exists(\App\Services\NotificationService::class)) {
                app(\App\Services\NotificationService::class)->sendToUser(
                    $user,
                    $title,
                    $body,
                    [
                        'type' => 'payment_result',
                        'transaction_id' => (string) $transaction->id,
                        'status' => $transaction->status,
                    ]
                );
            }

        } catch (\Exception $e) {
            Log::warning('Erreur envoi notification paiement', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
