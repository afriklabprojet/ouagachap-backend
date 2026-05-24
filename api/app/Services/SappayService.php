<?php

namespace App\Services;

use App\Models\SappayTransaction;
use App\Models\User;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SappayService
{
    private string $publicUrl;

    private string $checkoutUrl;

    private string $disburseUrl;

    private CircuitBreaker $cb;

    private const TOKEN_CACHE_KEY = 'sappay:access_token';

    private const CONTENT_TYPE_JSON = 'application/json';

    public function __construct(
        private readonly PushNotificationService $pushNotificationService,
    ) {
        $this->publicUrl   = config('sappay.public_url');
        $this->checkoutUrl = config('sappay.checkout_url');
        $this->disburseUrl = config('sappay.disburse_url');
        $this->cb          = new CircuitBreaker('sappay', threshold: 5, cooldown: 60);
    }

    // ==================== AUTH ====================

    /**
     * Obtenir (ou renouveler) le token d'accès Sappay.
     * Le token est mis en cache jusqu'à expiration.
     */
    public function getAccessToken(): ?string
    {
        return Cache::remember(self::TOKEN_CACHE_KEY, config('sappay.token_ttl', 3500), function () {
            $response = Http::withHeaders([
                'Content-Type' => self::CONTENT_TYPE_JSON,
                'Accept'       => self::CONTENT_TYPE_JSON,
            ])->timeout(15)->post("{$this->publicUrl}/authentication/", [
                'grant_type'    => 'password',
                'client_id'     => config('sappay.client_id'),
                'client_secret' => config('sappay.client_secret'),
                'username'      => config('sappay.username'),
                'password'      => config('sappay.password'),
            ]);

            if (! $response->successful()) {
                Log::error('Sappay: échec authentification', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);

                return null;
            }

            return $response->json('access_token');
        });
    }

    // ==================== HTTP HELPER ====================

    private function request(string $baseUrl, string $method, string $path, array $data = [], bool $retried = false, bool $returnBodyOnFailure = false): ?array
    {
        $token = $this->getAccessToken();

        if (! $token) {
            return null;
        }

        $response = Http::withHeaders([
            'Content-Type'  => self::CONTENT_TYPE_JSON,
            'Accept'        => self::CONTENT_TYPE_JSON,
            'Authorization' => "Bearer {$token}",
        ])->timeout(15)->connectTimeout(5)->{$method}("{$baseUrl}{$path}", $data);

        // Renouveler le token et retenter une seule fois si 401
        if ($response->status() === 401 && ! $retried) {
            Cache::forget(self::TOKEN_CACHE_KEY);

            return $this->request($baseUrl, $method, $path, $data, true, $returnBodyOnFailure);
        }

        return $this->parseResponse($response, $method, $path, $returnBodyOnFailure);
    }

    private function parseResponse(Response $response, string $method, string $path, bool $returnBodyOnFailure): ?array
    {
        if (! $response->successful()) {
            Log::error("Sappay API error [{$method} {$path}]", [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            if ($returnBodyOnFailure && $response->status() < 500) {
                return $response->json() ?? [
                    'status'  => $response->status(),
                    'message' => $response->body(),
                ];
            }

            $this->cb->recordFailure();

            return null;
        }

        $this->cb->recordSuccess();

        return $response->json();
    }

    // ==================== FACTURE ====================

    /**
     * Étape 1 : Créer une facture SIMPLE.
     * Retourne l'invoice_id ou null en cas d'échec.
     */
    public function createInvoice(int $amountFcfa, string $note, string $email = ''): ?string
    {
        $data = $this->request($this->publicUrl, 'post', '/invoice/', [
            'type'     => 'SIMPLE',
            'customer' => [
                'email'   => $email ?: 'client@ouagachap.app',
                'country' => config('sappay.default_country', 1),
            ],
            'amount' => (string) $amountFcfa,
            'note'   => $note,
        ]);

        // La réponse réelle est imbriquée : response.invoice_detail.invoice_id
        $invoiceId = $data['response']['invoice_detail']['invoice_id']
            ?? $data['invoice_id']
            ?? null;

        if (! $invoiceId) {
            Log::error('Sappay: createInvoice failed', ['amount' => $amountFcfa, 'note' => $note, 'response' => $data]);

            return null;
        }

        return $invoiceId;
    }

    // ==================== OTP ====================

    /**
     * Étape 2 (optionnelle selon opérateur) : Envoyer un OTP au client.
     */
    public function getOtp(string $invoiceId, string $paymentProcessorId, string $customerMsisdn): bool
    {
        $data = $this->request($this->checkoutUrl, 'post', '/get-otp/', [
            'invoice_id'           => $invoiceId,
            'payment_processor_id' => $paymentProcessorId,
            'customer_msisdn'      => $customerMsisdn,
        ]);

        return $data !== null;
    }

    // ==================== PERFORM ====================

    /**
     * Étape 3 : Exécuter le paiement avec l'OTP.
     * Retourne le tableau de réponse ou null.
     */
    public function performPayment(
        string $invoiceId,
        string $paymentProcessorId,
        string $customerMsisdn,
        string $otp,
        ?string $transId = null
    ): ?array {
        $body = [
            'invoice_id'           => $invoiceId,
            'payment_processor_id' => $paymentProcessorId,
            'customer_msisdn'      => $customerMsisdn,
            'otp'                  => $otp,
        ];

        if ($transId) {
            $body['trans_id'] = $transId;
        }

        return $this->request($this->checkoutUrl, 'post', '/perform/', $body, returnBodyOnFailure: true);
    }

    // ==================== FLUX PRINCIPAL ====================

    /**
     * Initier un paiement complet (crée la facture + envoie l'OTP si nécessaire).
     * Retourne les données nécessaires à l'app mobile pour l'écran OTP.
     */
    public function initiatePayment(
        User $user,
        int $amountFcfa,
        string $paymentMethod,
        string $customerMsisdn,
        string $type = 'wallet_recharge',
        array $metadata = []
    ): array {
        if ($this->cb->isOpen()) {
            return ['success' => false, 'message' => 'Service de paiement temporairement indisponible.'];
        }

        return $this->validateAndInitiatePayment($user, $amountFcfa, $paymentMethod, $customerMsisdn, $type, $metadata);
    }

    private function validateAndInitiatePayment(
        User $user,
        int $amountFcfa,
        string $paymentMethod,
        string $customerMsisdn,
        string $type,
        array $metadata
    ): array {
        [$min, $max] = [config('sappay.min_amount', 100), config('sappay.max_amount', 1000000)];

        if ($amountFcfa < $min || $amountFcfa > $max) {
            return ['success' => false, 'message' => "Montant invalide (entre {$min} et {$max} FCFA)"];
        }

        if (! config("sappay.payment_methods.{$paymentMethod}")) {
            return ['success' => false, 'message' => "Moyen de paiement invalide : {$paymentMethod}"];
        }

        return $this->executePayment($user, $amountFcfa, $paymentMethod, $customerMsisdn, $type, $metadata);
    }

    private function executePayment(
        User $user,
        int $amountFcfa,
        string $paymentMethod,
        string $customerMsisdn,
        string $type,
        array $metadata
    ): array {
        try {
            $reference = $this->generateReference($type);
            $invoiceId = $this->createInvoice($amountFcfa, "OUAGA CHAP - {$reference}", $user->email ?? '');

            if (! $invoiceId) {
                return ['success' => false, 'message' => 'Impossible de créer la facture. Réessayez.'];
            }

            $methodConfig  = config("sappay.payment_methods.{$paymentMethod}");
            $processorId   = $methodConfig['payment_processor_id'];
            $requiresOtp   = $methodConfig['requires_get_otp'] ?? false;
            $otpSent       = false;

            if ($requiresOtp) {
                $otpSent = $this->getOtp($invoiceId, $processorId, $customerMsisdn);
                if (! $otpSent) {
                    Log::warning('Sappay: get-otp failed but continuing', [
                        'invoice_id' => $invoiceId,
                        'method'     => $paymentMethod,
                    ]);
                }
            }

            $transaction = SappayTransaction::create([
                'user_id'              => $user->id,
                'invoice_id'           => $invoiceId,
                'reference'            => $reference,
                'type'                 => $type,
                'payment_method'       => $paymentMethod,
                'payment_processor_id' => $processorId,
                'customer_msisdn'      => $customerMsisdn,
                'amount'               => $amountFcfa,
                'currency'             => 'XOF',
                'status'               => 'pending',
                'requires_otp'         => $requiresOtp,
                'metadata'             => $metadata,
            ]);

            Log::info('Sappay: paiement initié', [
                'transaction_id' => $transaction->id,
                'invoice_id'     => $invoiceId,
                'method'         => $paymentMethod,
                'amount'         => $amountFcfa,
            ]);

            return [
                'success' => true,
                'data'    => [
                    'transaction_id' => $transaction->id,
                    'invoice_id'     => $invoiceId,
                    'reference'      => $reference,
                    'requires_otp'   => $requiresOtp,
                    'otp_sent'       => $otpSent,
                    'payment_method' => $paymentMethod,
                    'amount'         => $amountFcfa,
                    'processor_id'   => $processorId,
                ],
                'message' => $requiresOtp
                    ? 'OTP envoyé sur votre téléphone. Saisissez le code pour confirmer.'
                    : 'Saisissez le code OTP reçu sur votre téléphone.',
            ];

        } catch (\Exception $e) {
            Log::error('Sappay: initiatePayment exception', [
                'error'   => $e->getMessage(),
                'user_id' => $user->id,
                'amount'  => $amountFcfa,
            ]);

            return ['success' => false, 'message' => 'Une erreur inattendue est survenue.'];
        }
    }

    /**
     * Confirmer le paiement avec l'OTP saisi par l'utilisateur.
     */
    public function confirmPayment(
        SappayTransaction $transaction,
        string $otp,
        ?string $transId = null
    ): array {
        if (! $transaction->isPending()) {
            return ['success' => false, 'message' => 'Transaction déjà traitée.'];
        }

        try {
            $result = $this->performPayment(
                invoiceId: $transaction->invoice_id,
                paymentProcessorId: $transaction->payment_processor_id,
                customerMsisdn: $transaction->customer_msisdn,
                otp: $otp,
                transId: $transId
            );

            return $this->processPerformResult($transaction, $result);

        } catch (\Exception $e) {
            Log::error('Sappay: confirmPayment exception', [
                'error'          => $e->getMessage(),
                'transaction_id' => $transaction->id,
            ]);

            return ['success' => false, 'message' => 'Une erreur est survenue lors de la confirmation.'];
        }
    }

    private function processPerformResult(SappayTransaction $transaction, ?array $result): array
    {
        if (! $result) {
            $transaction->markAsError('Échec de la confirmation OTP');

            return ['success' => false, 'message' => 'OTP invalide ou paiement refusé.'];
        }

        Log::info('Sappay: performPayment raw response', [
            'transaction_id' => $transaction->id,
            'invoice_id'     => $transaction->invoice_id,
            'result'         => $result,
        ]);

        $statusRaw = $result['response']['invoice_detail']['status']
            ?? $result['invoice_details']['status']
            ?? $result['status']
            ?? '';

        $status    = strtoupper((string) $statusRaw);
        $isSuccess = $status === 'SUCCESS' || $status === '200' || (int) $statusRaw === 200;

        if ($isSuccess) {
            $transaction->markAsSuccess($result);
            $this->processSuccessfulPayment($transaction);

            return [
                'success' => true,
                'data'    => ['transaction_id' => $transaction->id, 'status' => 'success'],
                'message' => 'Paiement effectué avec succès.',
            ];
        }

        Log::warning('Sappay: statut inattendu après perform', [
            'transaction_id' => $transaction->id,
            'status_raw'     => $statusRaw,
            'result'         => $result,
        ]);

        $failureMessage = $this->paymentFailureMessage($result);
        $transaction->markAsError($failureMessage);

        return ['success' => false, 'message' => $failureMessage];
    }

    protected function paymentFailureMessage(array $result): string
    {
        $gatewayMessage = (string) (
            $result['response']['gateway_message']
            ?? $result['gateway_message']
            ?? $result['message']
            ?? ''
        );

        if (Str::contains(Str::lower($gatewayMessage), 'otp does not exist')) {
            return 'OTP introuvable ou expiré. Générez un nouvel OTP Orange Money, puis réessayez.';
        }

        if ($gatewayMessage !== '') {
            return "Paiement refusé par l'opérateur : {$gatewayMessage}";
        }

        return 'Paiement refusé par l\'opérateur.';
    }

    /**
     * Traiter un webhook Sappay (confirmation asynchrone).
     *
     * Sappay ne fournit pas de signature HMAC sur ses webhooks. La défense est
     * de NE PAS faire confiance au statut fourni dans le payload : on interroge
     * directement l'API Sappay pour confirmer le statut réel de la facture.
     */
    public function handleWebhook(array $payload): array
    {
        $invoiceDetails = $payload['invoice_details'] ?? [];
        $invoiceId      = $invoiceDetails['invoice_id'] ?? null;

        if (! $invoiceId) {
            Log::warning('Sappay Webhook: invoice_id manquant', ['payload' => $payload]);

            return ['success' => false, 'message' => 'invoice_id manquant'];
        }

        // Re-vérifier le statut directement chez Sappay — ne pas faire confiance au payload
        $verifiedStatus = $this->fetchInvoiceStatusFromApi($invoiceId);

        if ($verifiedStatus === null) {
            Log::warning('Sappay Webhook: impossible de vérifier le statut', ['invoice_id' => $invoiceId]);
            // Accepter HTTP 200 pour éviter les retentatives, mais ne pas créditer
            return ['success' => true, 'message' => 'Vérification en attente'];
        }

        return $this->processWebhookTransaction($invoiceId, $verifiedStatus, $payload);
    }

    /**
     * Interroge l'API Sappay pour obtenir le statut réel d'une facture.
     * Retourne le statut normalisé ('SUCCESS', 'FAILED', etc.) ou null si l'appel échoue.
     */
    private function fetchInvoiceStatusFromApi(string $invoiceId): ?string
    {
        $data = $this->request($this->publicUrl, 'get', "/get-invoice-detail/{$invoiceId}/");

        if ($data === null) {
            return null;
        }

        $rawStatus = $data['response']['invoice_detail']['status']
            ?? $data['invoice_details']['status']
            ?? $data['status']
            ?? null;

        return $rawStatus !== null ? strtoupper((string) $rawStatus) : null;
    }

    private function processWebhookTransaction(string $invoiceId, string $status, array $payload): array
    {
        $transaction = SappayTransaction::where('invoice_id', $invoiceId)->first();

        if (! $transaction) {
            Log::warning('Sappay Webhook: transaction non trouvée', ['invoice_id' => $invoiceId]);

            return ['success' => false, 'message' => 'Transaction non trouvée'];
        }

        if ($transaction->status === 'success') {
            return ['success' => true, 'message' => 'Transaction déjà traitée'];
        }

        $this->applyWebhookStatus($transaction, $status, $payload);

        Log::info('Sappay Webhook traité', ['invoice_id' => $invoiceId, 'status' => $status]);

        return ['success' => true, 'message' => 'Webhook traité'];
    }

    private function applyWebhookStatus(SappayTransaction $transaction, string $status, array $payload): void
    {
        if ($status === 'SUCCESS') {
            $transaction->markAsSuccess($payload);
            $this->processSuccessfulPayment($transaction);
        } elseif (in_array($status, ['FAILED', 'ERROR', 'CANCELLED'])) {
            $transaction->markAsError("Webhook Sappay : {$status}");
        }
    }

    // ==================== POST-PAIEMENT ====================

    protected function processSuccessfulPayment(SappayTransaction $transaction): void
    {
        $user = $transaction->user;

        if ($transaction->type === 'wallet_recharge') {
            // Transaction atomique : crédit wallet + historique en une seule opération
            DB::transaction(function () use ($transaction, $user) {
                $user->addToWallet((float) $transaction->amount);

                \App\Models\WalletTransaction::create([
                    'user_id'                 => $transaction->user_id,
                    'type'                    => 'recharge',
                    'amount'                  => $transaction->amount,
                    'method'                  => $transaction->payment_method,
                    'phone_number'            => $transaction->customer_msisdn,
                    'status'                  => 'success',
                    'provider_transaction_id' => $transaction->invoice_id,
                    'completed_at'            => now(),
                ]);
            });

            $newBalance = $user->fresh()->wallet_balance;

            Log::info('Sappay: wallet crédité', [
                'user_id'     => $user->id,
                'amount'      => $transaction->amount,
                'invoice_id'  => $transaction->invoice_id,
                'new_balance' => $newBalance,
            ]);

            \Sentry\configureScope(function (\Sentry\State\Scope $scope) use ($transaction, $user, $newBalance): void {
                $scope->setContext('business_event', [
                    'event'       => 'wallet_recharge_success',
                    'user_id'     => $user->id,
                    'amount_fcfa' => $transaction->amount,
                    'method'      => $transaction->payment_method,
                    'invoice_id'  => $transaction->invoice_id,
                    'new_balance' => $newBalance,
                ]);
                \Sentry\captureMessage(
                    "[BUSINESS] Recharge wallet {$transaction->amount} FCFA — user #{$user->id}",
                    \Sentry\Severity::info()
                );
            });

            try {
                $this->pushNotificationService->sendToUser(
                    $user,
                    'Recharge réussie !',
                    "Votre compte a été crédité de {$transaction->amount} FCFA",
                    ['type' => 'wallet_recharged', 'amount' => $transaction->amount]
                );
            } catch (\Exception $e) {
                Log::warning('Sappay: notification recharge échouée', ['error' => $e->getMessage()]);
            }
        } elseif ($transaction->type === 'order_payment') {
            $paymentId = $transaction->metadata['payment_id'] ?? null;
            if ($paymentId) {
                $payment = \App\Models\Payment::find($paymentId);
                if ($payment && $payment->isPending()) {
                    $payment->markAsSuccess(
                        $transaction->invoice_id,
                        json_encode(['sappay_invoice' => $transaction->invoice_id, 'method' => $transaction->payment_method])
                    );

                    \Sentry\configureScope(function (\Sentry\State\Scope $scope) use ($transaction, $payment): void {
                        $scope->setContext('business_event', [
                            'event'       => 'order_payment_success',
                            'user_id'     => $transaction->user_id,
                            'order_id'    => $payment->order_id,
                            'payment_id'  => $payment->id,
                            'amount_fcfa' => $transaction->amount,
                            'method'      => $transaction->payment_method,
                            'invoice_id'  => $transaction->invoice_id,
                        ]);
                        \Sentry\captureMessage(
                            "[BUSINESS] Paiement commande {$transaction->amount} FCFA — order #{$payment->order_id}",
                            \Sentry\Severity::info()
                        );
                    });
                }
            }
        }
    }

    // ==================== DISBURSEMENT ====================

    /**
     * Virement sortant (B2C) vers un coursier.
     *
     * Appelle l'endpoint Sappay de décaissement, enregistre une SappayTransaction
     * de type 'withdrawal' et retourne le résultat.
     *
     * @param  User   $courier        Le coursier destinataire du virement
     * @param  int    $amountFcfa     Montant en FCFA
     * @param  string $paymentMethod  Code opérateur (orange_money, moov_money, …)
     * @param  string $msisdn         Numéro de téléphone du coursier
     * @param  string $withdrawalRef  Référence interne du retrait (Withdrawal::id)
     * @return array  ['success' => bool, 'reference' => string|null, 'message' => string]
     */
    public function disburse(
        User $courier,
        int $amountFcfa,
        string $paymentMethod,
        string $msisdn,
        string $withdrawalRef
    ): array {
        if ($this->cb->isOpen()) {
            return ['success' => false, 'reference' => null, 'message' => 'Service de paiement temporairement indisponible.'];
        }

        $methodConfig = config("sappay.payment_methods.{$paymentMethod}");
        if (! $methodConfig) {
            return ['success' => false, 'reference' => null, 'message' => "Moyen de paiement invalide : {$paymentMethod}"];
        }

        $processorId = $methodConfig['payment_processor_id'];
        $reference   = $this->generateReference('withdrawal');

        try {
            $response = $this->request($this->disburseUrl, 'post', '/disburse/', [
                'payment_processor_id' => $processorId,
                'customer_msisdn'      => $msisdn,
                'amount'               => (string) $amountFcfa,
                'reference'            => $withdrawalRef,
                'note'                 => "OUAGA CHAP - Retrait coursier {$withdrawalRef}",
            ]);

            $status = strtoupper(
                $response['response']['status']
                ?? $response['status']
                ?? ''
            );

            $isSuccess = $response !== null && ($status === 'SUCCESS' || $status === '200' || (int) ($response['response']['status'] ?? 0) === 200);

            SappayTransaction::create([
                'user_id'              => $courier->id,
                'invoice_id'           => $response['response']['invoice_id'] ?? $reference,
                'reference'            => $reference,
                'type'                 => 'withdrawal',
                'payment_method'       => $paymentMethod,
                'payment_processor_id' => $processorId,
                'customer_msisdn'      => $msisdn,
                'amount'               => $amountFcfa,
                'currency'             => 'XOF',
                'status'               => $isSuccess ? 'success' : 'error',
                'requires_otp'         => false,
                'metadata'             => ['withdrawal_ref' => $withdrawalRef],
            ]);

            if ($isSuccess) {
                Log::info('Sappay disburse: succès', [
                    'courier_id'    => $courier->id,
                    'amount'        => $amountFcfa,
                    'method'        => $paymentMethod,
                    'reference'     => $reference,
                    'withdrawal_ref' => $withdrawalRef,
                ]);

                return ['success' => true, 'reference' => $reference, 'message' => 'Virement effectué avec succès.'];
            }

            $errorMsg = $response['response']['gateway_message']
                ?? $response['message']
                ?? 'Virement refusé par l\'opérateur.';

            Log::error('Sappay disburse: échec opérateur', [
                'courier_id'    => $courier->id,
                'amount'        => $amountFcfa,
                'method'        => $paymentMethod,
                'reference'     => $reference,
                'response'      => $response,
            ]);

            return ['success' => false, 'reference' => null, 'message' => $errorMsg];

        } catch (\Exception $e) {
            Log::error('Sappay disburse: exception', [
                'courier_id'    => $courier->id,
                'amount'        => $amountFcfa,
                'error'         => $e->getMessage(),
            ]);

            return ['success' => false, 'reference' => null, 'message' => 'Erreur lors du virement : ' . $e->getMessage()];
        }
    }

    // ==================== HELPERS ====================

    protected function generateReference(string $type): string
    {
        $prefix = match ($type) {
            'wallet_recharge' => 'RCH',
            'order_payment'   => 'ORD',
            'withdrawal'      => 'WDR',
            default           => 'PAY',
        };

        return "{$prefix}-".now()->format('YmdHis').'-'.strtoupper(Str::random(6));
    }

    public function getAvailablePaymentMethods(): array
    {
        return collect(config('sappay.payment_methods', []))
            ->map(fn ($m, $code) => [
                'code'             => $code,
                'name'             => $m['name'],
                'icon'             => $m['icon'],
                'color'            => $m['color'],
                'requires_get_otp' => $m['requires_get_otp'],
            ])
            ->values()
            ->toArray();
    }

    public function getMethodName(string $code): string
    {
        return config("sappay.payment_methods.{$code}.name", ucfirst($code));
    }
}
