<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\SappayTransaction;
use App\Services\SappayService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SappayController extends BaseController
{
    public function __construct(
        private SappayService $sappay
    ) {}

    /**
     * Lister les moyens de paiement disponibles.
     */
    public function paymentMethods(): JsonResponse
    {
        return $this->success($this->sappay->getAvailablePaymentMethods());
    }

    /**
     * Étape 1 : Initier une recharge de wallet.
     * Crée la facture Sappay, envoie l'OTP si nécessaire.
     * Retourne les infos pour afficher l'écran OTP dans l'app.
     */
    public function initiateWalletRecharge(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount'           => 'required|numeric|min:100|max:1000000',
            'payment_method'   => 'required|string|in:orange_money,telecel_money,moov_money,coris_money',
            'customer_msisdn'  => 'required|string|min:8|max:20',
        ]);

        $result = $this->sappay->initiatePayment(
            user:            $request->user(),
            amountFcfa:      (int) $validated['amount'],
            paymentMethod:   $validated['payment_method'],
            customerMsisdn:  $validated['customer_msisdn'],
            type:            'wallet_recharge',
            metadata:        ['description' => "Recharge portefeuille {$validated['amount']} FCFA"]
        );

        if (!$result['success']) {
            return $this->error($result['message'] ?? 'Erreur lors de l\'initiation du paiement');
        }

        return $this->success($result['data'], $result['message']);
    }

    /**
     * Étape 1 : Initier un paiement de commande.
     */
    public function initiateOrderPayment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id'        => 'required|exists:orders,id',
            'payment_method'  => 'required|string|in:orange_money,telecel_money,moov_money,coris_money',
            'customer_msisdn' => 'required|string|min:8|max:20',
        ]);

        $user  = $request->user();
        $order = \App\Models\Order::findOrFail($validated['order_id']);

        if ($order->client_id !== $user->id) {
            return $this->forbidden("Vous n'êtes pas autorisé à payer cette commande");
        }

        if ($order->payments()->where('status', PaymentStatus::SUCCESS)->exists()) {
            return $this->error('Cette commande est déjà payée');
        }

        // Crée (ou récupère) l'enregistrement Payment avant de contacter Sappay,
        // pour que SappayService::processSuccessfulPayment() puisse le retrouver via metadata['payment_id'].
        $payment = DB::transaction(function () use ($order, $user, $validated) {
            return Payment::updateOrCreate(
                ['order_id' => $order->id],
                [
                    'user_id'      => $user->id,
                    'amount'       => $order->total_price,
                    'method'       => PaymentMethod::from($validated['payment_method']),
                    'status'       => PaymentStatus::PENDING,
                    'phone_number' => $validated['customer_msisdn'],
                ]
            );
        });

        $result = $this->sappay->initiatePayment(
            user:           $user,
            amountFcfa:     (int) $order->total_price,
            paymentMethod:  $validated['payment_method'],
            customerMsisdn: $validated['customer_msisdn'],
            type:           'order_payment',
            metadata:       [
                'order_id'    => $order->id,
                'payment_id'  => $payment->id,
                'description' => "Paiement commande #{$order->order_number}",
            ]
        );

        if (!$result['success']) {
            return $this->error($result['message'] ?? 'Erreur lors de l\'initiation du paiement');
        }

        return $this->success(array_merge($result['data'], [
            'order' => [
                'id'           => $order->id,
                'order_number' => $order->order_number,
                'total'        => $order->total_price,
            ],
        ]), $result['message']);
    }

    /**
     * Étape 2 : Confirmer le paiement avec l'OTP saisi par l'utilisateur.
     */
    public function confirmPayment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'transaction_id' => 'required|integer',
            'otp'            => 'required|string|min:4|max:10',
            'trans_id'       => 'nullable|string',
        ]);

        $user = $request->user();

        try {
            $transaction = SappayTransaction::where('id', $validated['transaction_id'])
                ->where('user_id', $user->id)
                ->firstOrFail();
        } catch (ModelNotFoundException) {
            return $this->notFound('Transaction non trouvée.');
        }

        $result = $this->sappay->confirmPayment(
            transaction: $transaction,
            otp:         $validated['otp'],
            transId:     $validated['trans_id'] ?? null
        );

        if (!$result['success']) {
            return $this->error($result['message'] ?? 'Paiement refusé');
        }

        return $this->success($result['data'], $result['message']);
    }

    /**
     * Vérifier le statut d'une transaction.
     */
    public function checkStatus(Request $request, int $transactionId): JsonResponse
    {
        $user = $request->user();

        try {
            $transaction = SappayTransaction::where('id', $transactionId)
                ->where('user_id', $user->id)
                ->firstOrFail();
        } catch (ModelNotFoundException) {
            return $this->notFound('Transaction non trouvée.');
        }

        return $this->success([
            'id'                   => $transaction->id,
            'invoice_id'           => $transaction->invoice_id,
            'reference'            => $transaction->reference,
            'type'                 => $transaction->type,
            'amount'               => $transaction->amount,
            'currency'             => $transaction->currency,
            'status'               => $transaction->status,
            'status_label'         => $transaction->status_label,
            'payment_method'       => $transaction->payment_method,
            'payment_method_name'  => $transaction->payment_method_name,
            'requires_otp'         => $transaction->requires_otp,
            'created_at'           => $transaction->created_at,
            'executed_at'          => $transaction->executed_at,
        ]);
    }

    /**
     * Historique des transactions de l'utilisateur connecté.
     */
    public function transactionHistory(Request $request): JsonResponse
    {
        $transactions = SappayTransaction::where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        return $this->paginated($transactions, 'Historique des transactions.');
    }

    /**
     * Webhook Sappay — confirmation asynchrone des paiements.
     *
     * Deux couches de défense :
     *  1. Signature HMAC-SHA256 (header X-Sappay-Signature) — fail-closed en production.
     *     Sappay n'a pas encore documenté leur schéma ; dès réception du secret,
     *     renseigner SAPPAY_WEBHOOK_SECRET dans .env et mettre à jour le nom du header
     *     si nécessaire.
     *  2. Re-vérification du statut directement via l'API Sappay (dans SappayService) —
     *     ne jamais faire confiance au statut fourni dans le payload.
     */
    public function webhook(Request $request): JsonResponse
    {
        $rawBody = $request->getContent();

        if (!$this->validateSappaySignature($rawBody, $request->header('X-Sappay-Signature', ''))) {
            Log::channel('security')->warning('Sappay Webhook: signature invalide', [
                'ip' => $request->ip(),
            ]);
            return response()->json(['success' => false, 'message' => 'Signature invalide'], 403);
        }

        $payload = $request->all();

        Log::info('Sappay Webhook reçu', [
            'ip'         => $request->ip(),
            'invoice_id' => $payload['invoice_details']['invoice_id'] ?? 'unknown',
        ]);

        $result = $this->sappay->handleWebhook($payload);

        if (!$result['success']) {
            return $this->error($result['message'], 422);
        }

        return $this->success(null, $result['message']);
    }

    /**
     * Valider la signature HMAC-SHA256 du webhook Sappay.
     *
     * Si SAPPAY_WEBHOOK_SECRET n'est pas configuré :
     *  - production → 403 (fail-closed)
     *  - autres envs → laisse passer (développement local)
     *
     * Format attendu du header : sha256=<hex> (même convention que Meta/Infobip).
     * À ajuster dès que Sappay publie leur documentation de signature.
     */
    private function validateSappaySignature(string $rawBody, string $signature): bool
    {
        $secret = config('sappay.webhook_secret');

        if (!$secret) {
            if (config('app.env') === 'production') {
                return false;
            }
            return true;
        }

        if ($signature === '') {
            return false;
        }

        $expected = 'sha256=' . hash_hmac('sha256', $rawBody, $secret);

        return hash_equals($expected, $signature);
    }
}
