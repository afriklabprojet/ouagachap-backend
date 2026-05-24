<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    protected string $driver;

    private CircuitBreaker $cb;

    public function __construct()
    {
        $this->driver = config('sms.default')
            ?? config('sms.default_driver', 'log');
        $this->cb = new CircuitBreaker('sms_infobip', threshold: 5, cooldown: 120);
    }

    /**
     * Envoyer un code OTP par SMS.
     *
     * @return array{success: bool, error?: string}
     */
    public function sendOtp(string $phone, string $code): array
    {
        $phone = $this->normalizePhone($phone);
        $message = "OUAGA CHAP : Votre code de vérification est {$code}. Il expire dans 5 minutes. Ne le partagez pas.";

        return $this->send($phone, $message);
    }

    /**
     * Envoyer un SMS libre.
     *
     * @return array{success: bool, error?: string}
     */
    public function send(string $phone, string $message): array
    {
        $phone = $this->normalizePhone($phone);

        return match ($this->driver) {
            'infobip' => $this->sendViaInfobip($phone, $message),
            'twilio'  => $this->sendViaTwilio($phone, $message),
            'brevo'   => $this->sendViaBrevo($phone, $message),
            default   => $this->sendViaLog($phone, $message),
        };
    }

    private function sendViaInfobip(string $phone, string $message): array
    {
        if ($this->cb->isOpen()) {
            Log::warning('SmsService: circuit Infobip ouvert — SMS ignoré', ['phone_prefix' => substr($phone, 0, 7)]);
            return ['success' => false, 'error' => 'Service SMS temporairement indisponible.'];
        }

        $apiKey  = config('services.infobip.api_key');
        $baseUrl = config('services.infobip.base_url');
        $from    = config('services.infobip.from', 'OUA CHAP');

        if (!$apiKey || !$baseUrl) {
            Log::error('SmsService: identifiants Infobip non configurés.');
            return ['success' => false, 'error' => 'Infobip credentials missing.'];
        }

        try {
            // Infobip exige le numéro sans le préfixe +
            $infobipPhone = ltrim($phone, '+');

            $smsMessage = [
                'from' => $from,
                'destinations' => [
                    ['to' => $infobipPhone],
                ],
                'text' => $message,
            ];

            // Translitération (ex. CENTRAL_EUROPEAN pour les caractères spéciaux)
            $transliteration = config('services.infobip.transliteration');
            if ($transliteration) {
                $smsMessage['transliteration'] = $transliteration;
            }

            // Durée de validité du message en minutes (entier pour l'API Infobip)
            $validityPeriod = (int) config('services.infobip.validity_period', 0);
            if ($validityPeriod > 0) {
                $smsMessage['validityPeriod'] = $validityPeriod;
            }

            // Suivi de livraison et webhook
            if (config('services.infobip.track_delivery', false)) {
                $webhookUrl = config('services.infobip.webhook_url');
                if ($webhookUrl) {
                    $smsMessage['notifyUrl']            = $webhookUrl;
                    $smsMessage['notifyContentType']    = 'application/json';
                    $smsMessage['callbackData']         = config('services.infobip.callback_prefix', '') . $phone;
                    $smsMessage['intermediateReport']    = config('services.infobip.intermediate_reports', false);
                }
            }

            $response = Http::withHeaders([
                'Authorization' => "App {$apiKey}",
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ])->post(rtrim($baseUrl, '/') . '/sms/2/text/advanced', [
                'messages' => [$smsMessage],
            ]);

            if ($response->successful()) {
                $body = $response->json();
                $status = $body['messages'][0]['status']['groupName'] ?? 'UNKNOWN';

                if (in_array($status, ['PENDING', 'ACCEPTED'])) {
                    $this->cb->recordSuccess();
                    $this->logSms('SmsService: SMS envoyé via Infobip', [
                        'phone'    => substr($phone, 0, 7) . '****',
                        'status'   => $status,
                        'messageId' => $body['messages'][0]['messageId'] ?? null,
                    ]);
                    return ['success' => true];
                }

                Log::warning('SmsService: Infobip status inattendu', [
                    'phone'  => substr($phone, 0, 7) . '****',
                    'status' => $status,
                    'description' => $body['messages'][0]['status']['description'] ?? '',
                ]);
                return ['success' => false, 'error' => "Infobip status: {$status}"];
            }

            $this->cb->recordFailure();
            Log::error('SmsService: erreur HTTP Infobip', [
                'phone'  => substr($phone, 0, 7) . '****',
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            return ['success' => false, 'error' => 'Infobip HTTP error: ' . $response->status()];
        } catch (\Exception $e) {
            $this->cb->recordFailure();
            Log::error('SmsService: erreur Infobip', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function sendViaBrevo(string $phone, string $message): array
    {
        $apiKey = config('services.brevo.api_key');
        $sender = config('services.brevo.sender', 'OUAGACHAP');

        if (!$apiKey) {
            Log::error('SmsService: clé API Brevo non configurée.');
            return ['success' => false, 'error' => 'Brevo API key missing.'];
        }

        try {
            $response = Http::withHeaders([
                'api-key'      => $apiKey,
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ])->post('https://api.brevo.com/v3/transactionalSMS/send', [
                'sender'    => $sender,
                'recipient' => $phone,
                'content'   => $message,
                'type'      => 'transactional',
            ]);

            // 201 = succès
            if ($response->status() === 201) {
                $messageId = $response->json('messageId');
                $this->logSms('SmsService: SMS envoyé via Brevo', [
                    'phone'     => substr($phone, 0, 7) . '****',
                    'messageId' => $messageId,
                ]);
                return ['success' => true, 'messageId' => $messageId];
            }

            $body = $response->json();
            Log::error('SmsService: erreur HTTP Brevo', [
                'phone'  => substr($phone, 0, 7) . '****',
                'status' => $response->status(),
                'code'   => $body['code'] ?? null,
                'message' => $body['message'] ?? $response->body(),
            ]);
            return ['success' => false, 'error' => $body['message'] ?? 'Brevo HTTP error: ' . $response->status()];
        } catch (\Exception $e) {
            Log::error('SmsService: erreur Brevo', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function sendViaTwilio(string $phone, string $message): array
    {
        $sid   = config('services.twilio.sid');
        $token = config('services.twilio.token');
        $from  = config('services.twilio.from');

        if (!$sid || !$token || !$from) {
            Log::error('SmsService: identifiants Twilio non configurés.');
            return ['success' => false, 'error' => 'Twilio credentials missing.'];
        }

        // @codeCoverageIgnoreStart
        try {
            $client = new \Twilio\Rest\Client($sid, $token);
            $client->messages->create($phone, [
                'from' => $from,
                'body' => $message,
            ]);

            Log::info('SmsService: SMS envoyé via Twilio', [
                'phone' => substr($phone, 0, 7) . '****',
            ]);

            return ['success' => true];
        } catch (\Twilio\Exceptions\RestException $e) {
            Log::error('SmsService: erreur Twilio', [
                'code'  => $e->getCode(),
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        } catch (\Exception $e) {
            Log::error('SmsService: erreur inattendue', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
        // @codeCoverageIgnoreEnd
    }

    private function sendViaLog(string $phone, string $message): array
    {
        Log::info('SmsService [log]: SMS simulé', [
            'phone'   => $phone,
            'message' => $message,
        ]);

        return ['success' => true];
    }

    /**
     * Normaliser le numéro de téléphone au format E.164.
     */
    private function normalizePhone(string $phone): string
    {
        $countryCode = config('sms.default_country_code', '+226');
        $digits = ltrim($countryCode, '+'); // ex. "226"

        // Supprimer tout sauf chiffres et +
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        if (str_starts_with($phone, '00' . $digits)) {
            $phone = '+' . $digits . substr($phone, 2 + strlen($digits));
        } elseif (!str_starts_with($phone, '+')) {
            $phone = $countryCode . ltrim($phone, '0');
        }

        return $phone;
    }

    /**
     * Log conditionnel SMS (activé via SMS_LOG_MESSAGES).
     */
    private function logSms(string $message, array $context = []): void
    {
        if (config('sms.log_messages', false)) {
            $channel = config('sms.log_channel', 'sms');
            Log::channel($channel)->info($message, $context);
        } else {
            Log::info($message, $context);
        }
    }
}
