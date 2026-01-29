<?php

namespace App\Services;

use App\Models\JekoTransaction;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Service pour l'intégration du paiement mobile money via JEKO
 * Documentation: https://developer.jeko.africa/
 */
class JekoPaymentService
{
    protected string $baseUrl;
    protected string $apiKey;
    protected string $apiKeyId;
    protected string $storeId;
    protected string $currency;

    public function __construct()
    {
        $this->baseUrl = config('jeko.base_url');
        $this->apiKey = config('jeko.api_key');
        $this->apiKeyId = config('jeko.api_key_id');
        $this->storeId = config('jeko.store_id');
        $this->currency = config('jeko.currency');
    }

    /**
     * Créer une demande de paiement redirect (In-App Payment)
     *
     * @param User $user L'utilisateur qui effectue le paiement
     * @param int $amountFcfa Montant en FCFA
     * @param string $paymentMethod Méthode de paiement (wave, orange, mtn, moov, djamo)
     * @param string $type Type de transaction (recharge, order_payment, etc.)
     * @param array $metadata Données supplémentaires (order_id, etc.)
     * @return array ['success' => bool, 'data' => [...], 'message' => string]
     */
    public function createPaymentRequest(
        User $user,
        int $amountFcfa,
        string $paymentMethod,
        string $type = 'recharge',
        array $metadata = []
    ): array {
        // Validation du montant
        $minAmount = config('jeko.min_amount', 100);
        $maxAmount = config('jeko.max_amount', 1000000);
        
        if ($amountFcfa < $minAmount) {
            return [
                'success' => false,
                'message' => "Le montant minimum est de {$minAmount} FCFA",
            ];
        }
        
        if ($amountFcfa > $maxAmount) {
            return [
                'success' => false,
                'message' => "Le montant maximum est de {$maxAmount} FCFA",
            ];
        }

        // Validation de la méthode de paiement
        $validMethods = array_keys(config('jeko.payment_methods', []));
        if (!in_array($paymentMethod, $validMethods)) {
            return [
                'success' => false,
                'message' => "Méthode de paiement invalide: {$paymentMethod}",
            ];
        }

        try {
            // Générer une référence unique
            $reference = $this->generateReference($type);
            
            // Construire les URLs de callback
            $appScheme = config('jeko.app_scheme');
            $successUrl = "{$appScheme}://payment/success?reference={$reference}";
            $errorUrl = "{$appScheme}://payment/error?reference={$reference}";
            
            // Mode Sandbox/Mock pour les tests
            $isMockMode = config('jeko.sandbox') && (
                empty($this->apiKey) || 
                str_starts_with($this->apiKey, 'your_') ||
                $this->apiKey === 'test' ||
                $this->apiKey === 'mock'
            );
            
            if ($isMockMode) {
                return $this->mockPaymentRequest(
                    $user, $amountFcfa, $paymentMethod, $type, $reference, $metadata
                );
            }
            
            // Payload pour l'API JEKO
            $payload = [
                'storeId' => $this->storeId,
                'amountCents' => $amountFcfa * 100, // Convertir en centimes
                'currency' => $this->currency,
                'reference' => $reference,
                'paymentDetails' => [
                    'type' => 'redirect',
                    'data' => [
                        'paymentMethod' => $paymentMethod,
                        'successUrl' => $successUrl,
                        'errorUrl' => $errorUrl,
                    ],
                ],
            ];

            // Appel à l'API JEKO
            $response = Http::withHeaders([
                'X-API-KEY' => $this->apiKey,
                'X-API-KEY-ID' => $this->apiKeyId,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/partner_api/payment_requests", $payload);

            if (!$response->successful()) {
                Log::error('Jeko API Error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'payload' => $payload,
                ]);
                
                return [
                    'success' => false,
                    'message' => 'Erreur lors de la création du paiement. Veuillez réessayer.',
                ];
            }

            $data = $response->json();

            // Enregistrer la transaction en base
            $transaction = JekoTransaction::create([
                'user_id' => $user->id,
                'jeko_id' => $data['id'],
                'reference' => $reference,
                'type' => $type,
                'payment_method' => $paymentMethod,
                'amount' => $amountFcfa,
                'currency' => $this->currency,
                'status' => 'pending',
                'redirect_url' => $data['redirectUrl'] ?? null,
                'metadata' => $metadata,
            ]);

            Log::info('Jeko Payment Request Created', [
                'transaction_id' => $transaction->id,
                'jeko_id' => $data['id'],
                'reference' => $reference,
                'amount' => $amountFcfa,
            ]);

            return [
                'success' => true,
                'data' => [
                    'transaction_id' => $transaction->id,
                    'jeko_id' => $data['id'],
                    'reference' => $reference,
                    'redirect_url' => $data['redirectUrl'],
                    'status' => 'pending',
                    'amount' => $amountFcfa,
                    'payment_method' => $paymentMethod,
                ],
                'message' => 'Paiement initié avec succès',
            ];

        } catch (\Exception $e) {
            Log::error('Jeko Payment Exception', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'amount' => $amountFcfa,
            ]);
            
            return [
                'success' => false,
                'message' => 'Une erreur inattendue est survenue. Veuillez réessayer.',
            ];
        }
    }

    /**
     * Vérifier le statut d'une demande de paiement
     *
     * @param string $jekoId L'ID de la demande de paiement JEKO
     * @return array
     */
    public function getPaymentStatus(string $jekoId): array
    {
        try {
            $response = Http::withHeaders([
                'X-API-KEY' => $this->apiKey,
                'X-API-KEY-ID' => $this->apiKeyId,
            ])->get("{$this->baseUrl}/partner_api/payment_requests/{$jekoId}");

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'message' => 'Impossible de récupérer le statut du paiement',
                ];
            }

            $data = $response->json();

            return [
                'success' => true,
                'data' => [
                    'status' => $data['status'],
                    'payment_method' => $data['paymentMethod'] ?? null,
                    'transaction' => $data['transaction'] ?? null,
                ],
            ];

        } catch (\Exception $e) {
            Log::error('Jeko Status Check Exception', [
                'error' => $e->getMessage(),
                'jeko_id' => $jekoId,
            ]);
            
            return [
                'success' => false,
                'message' => 'Erreur lors de la vérification du statut',
            ];
        }
    }

    /**
     * Traiter un webhook de transaction complétée
     *
     * @param array $payload Le payload du webhook
     * @return array
     */
    public function handleWebhook(array $payload): array
    {
        $reference = $payload['transactionDetails']['reference'] ?? null;
        $status = $payload['status'] ?? 'unknown';
        $transactionType = $payload['transactionType'] ?? 'unknown';
        
        if (!$reference) {
            Log::warning('Jeko Webhook: Missing reference', ['payload' => $payload]);
            return ['success' => false, 'message' => 'Reference manquante'];
        }

        // Trouver la transaction
        $transaction = JekoTransaction::where('reference', $reference)->first();
        
        if (!$transaction) {
            Log::warning('Jeko Webhook: Transaction not found', ['reference' => $reference]);
            return ['success' => false, 'message' => 'Transaction non trouvée'];
        }

        // Éviter le traitement en double (idempotence)
        if ($transaction->status === 'success') {
            Log::info('Jeko Webhook: Transaction already processed', ['reference' => $reference]);
            return ['success' => true, 'message' => 'Transaction déjà traitée'];
        }

        // Mettre à jour la transaction
        $transaction->update([
            'status' => $status,
            'jeko_transaction_id' => $payload['id'] ?? null,
            'fees' => $payload['fees']['amount'] ?? 0,
            'counterpart_label' => $payload['counterpartLabel'] ?? null,
            'counterpart_identifier' => $payload['counterpartIdentifier'] ?? null,
            'executed_at' => isset($payload['executedAt']) ? new \DateTime($payload['executedAt']) : null,
            'webhook_payload' => $payload,
        ]);

        // Si le paiement est réussi, traiter selon le type
        if ($status === 'success') {
            $this->processSuccessfulPayment($transaction);
        }

        Log::info('Jeko Webhook Processed', [
            'reference' => $reference,
            'status' => $status,
            'transaction_id' => $transaction->id,
        ]);

        return ['success' => true, 'message' => 'Webhook traité'];
    }

    /**
     * Traiter un paiement réussi
     */
    protected function processSuccessfulPayment(JekoTransaction $transaction): void
    {
        $user = $transaction->user;
        
        switch ($transaction->type) {
            case 'recharge':
                // Créditer le wallet du client
                $user->increment('wallet_balance', $transaction->amount);
                
                // Enregistrer l'opération dans l'historique du wallet
                $user->walletTransactions()->create([
                    'type' => 'credit',
                    'amount' => $transaction->amount,
                    'balance_after' => $user->wallet_balance,
                    'description' => "Recharge via {$transaction->payment_method}",
                    'reference' => $transaction->reference,
                    'payment_method' => $transaction->payment_method,
                ]);
                
                // Envoyer une notification push
                try {
                    app(PushNotificationService::class)->sendToUser(
                        $user,
                        '💰 Recharge réussie !',
                        "Votre compte a été crédité de {$transaction->amount} FCFA via {$this->getPaymentMethodName($transaction->payment_method)}",
                        ['type' => 'wallet_credit', 'amount' => $transaction->amount]
                    );
                } catch (\Exception $e) {
                    Log::warning('Failed to send recharge notification', ['error' => $e->getMessage()]);
                }
                break;
                
            case 'order_payment':
                // Traiter le paiement d'une commande
                $orderId = $transaction->metadata['order_id'] ?? null;
                if ($orderId) {
                    // Marquer la commande comme payée
                    \App\Models\Order::where('id', $orderId)->update([
                        'payment_status' => 'paid',
                        'paid_at' => now(),
                    ]);
                }
                break;
        }
    }

    /**
     * Vérifier la signature HMAC-SHA256 du webhook
     *
     * @param string $payload Le corps brut de la requête
     * @param string $signature La signature reçue dans l'en-tête Jeko-Signature
     * @return bool
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        $secret = config('jeko.webhook_secret');
        
        if (empty($secret)) {
            Log::warning('Jeko: Webhook secret not configured');
            return false;
        }
        
        $expectedSignature = hash_hmac('sha256', $payload, $secret);
        
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Générer une référence unique pour la transaction
     */
    protected function generateReference(string $type): string
    {
        $prefix = match ($type) {
            'wallet_recharge' => 'RCH',
            'order_payment' => 'ORD',
            default => 'PAY',
        };
        
        $timestamp = now()->format('YmdHis');
        $random = strtoupper(Str::random(6));
        
        return "{$prefix}-{$timestamp}-{$random}";
    }

    /**
     * Obtenir le nom lisible d'une méthode de paiement
     */
    public function getPaymentMethodName(string $code): string
    {
        $methods = config('jeko.payment_methods', []);
        return $methods[$code]['name'] ?? ucfirst($code);
    }

    /**
     * Obtenir la liste des méthodes de paiement disponibles
     */
    public function getAvailablePaymentMethods(string $country = 'BF'): array
    {
        $methods = config('jeko.payment_methods', []);
        
        return collect($methods)
            ->filter(fn($method) => in_array($country, $method['countries'] ?? []))
            ->map(fn($method, $code) => [
                'code' => $code,
                'name' => $method['name'],
                'icon' => $method['icon'],
                'color' => $method['color'],
            ])
            ->values()
            ->toArray();
    }

    /**
     * Mode Mock pour les tests (sandbox sans clés API)
     */
    protected function mockPaymentRequest(
        User $user,
        int $amountFcfa,
        string $paymentMethod,
        string $type,
        string $reference,
        array $metadata
    ): array {
        $mockJekoId = 'mock_' . Str::uuid();
        
        // Enregistrer la transaction en base
        $transaction = JekoTransaction::create([
            'user_id' => $user->id,
            'jeko_id' => $mockJekoId,
            'reference' => $reference,
            'type' => $type,
            'payment_method' => $paymentMethod,
            'amount' => $amountFcfa,
            'currency' => $this->currency,
            'status' => 'pending',
            'redirect_url' => "http://localhost:8000/mock-payment?ref={$reference}&amount={$amountFcfa}",
            'metadata' => $metadata,
        ]);

        Log::info('Jeko MOCK Payment Request Created', [
            'transaction_id' => $transaction->id,
            'jeko_id' => $mockJekoId,
            'reference' => $reference,
            'amount' => $amountFcfa,
            'mode' => 'SANDBOX/MOCK',
        ]);

        return [
            'success' => true,
            'data' => [
                'transaction_id' => $transaction->id,
                'jeko_id' => $mockJekoId,
                'reference' => $reference,
                'redirect_url' => $transaction->redirect_url,
                'status' => 'pending',
                'amount' => $amountFcfa,
                'payment_method' => $paymentMethod,
            ],
            'message' => 'Paiement initié avec succès (MODE TEST)',
        ];
    }

    /**
     * Simuler la confirmation d'un paiement mock
     */
    public function mockConfirmPayment(string $reference): array
    {
        $transaction = JekoTransaction::where('reference', $reference)->first();
        
        if (!$transaction) {
            return ['success' => false, 'message' => 'Transaction non trouvée'];
        }

        if (!$transaction->isPending()) {
            return ['success' => false, 'message' => 'Transaction déjà traitée'];
        }

        $transaction->update([
            'status' => 'success',
            'counterpart_label' => 'Test User',
            'counterpart_identifier' => '70123456',
            'executed_at' => now(),
        ]);

        // Créditer le wallet si c'est une recharge
        if ($transaction->type === 'wallet_recharge') {
            $wallet = \App\Models\Wallet::firstOrCreate(
                ['user_id' => $transaction->user_id],
                ['balance' => 0]
            );
            $wallet->credit($transaction->amount);
            
            Log::info('Wallet crédité (mock)', [
                'user_id' => $transaction->user_id,
                'amount' => $transaction->amount,
                'new_balance' => $wallet->balance,
            ]);
        }

        return [
            'success' => true,
            'message' => 'Paiement confirmé (MODE TEST)',
            'transaction' => $transaction->fresh(),
        ];
    }
}
