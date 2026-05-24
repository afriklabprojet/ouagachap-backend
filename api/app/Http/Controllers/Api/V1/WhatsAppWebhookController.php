<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Webhook Meta WhatsApp Cloud API
 *
 * Deux endpoints :
 *  GET  /webhooks/whatsapp  → vérification du webhook par Meta (challenge handshake)
 *  POST /webhooks/whatsapp  → réception des événements (messages entrants, statuts)
 *
 * Documentation Meta :
 *  https://developers.facebook.com/docs/whatsapp/cloud-api/webhooks
 */
class WhatsAppWebhookController extends BaseController
{
    // ─────────────────────────────────────────────────────────────────────────
    // Vérification webhook (GET)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Vérifier le webhook WhatsApp (Meta challenge handshake).
     *
     * Meta envoie un GET avec :
     *   hub.mode=subscribe
     *   hub.verify_token=<votre token>
     *   hub.challenge=<chaîne à retourner>
     *
     * On doit répondre avec hub.challenge en texte brut (200) si le token correspond,
     * ou 403 sinon.
     */
    public function verify(Request $request): Response|JsonResponse
    {
        $mode        = $request->query('hub_mode')         ?? $request->query('hub.mode');
        $token       = $request->query('hub_verify_token') ?? $request->query('hub.verify_token');
        $challenge   = $request->query('hub_challenge')    ?? $request->query('hub.challenge');

        $expectedToken = config('services.whatsapp_cloud.webhook_verify_token');

        Log::info('WhatsAppWebhook: tentative de vérification', [
            'mode' => $mode,
            'ip'   => $request->ip(),
        ]);

        if ($mode === 'subscribe' && $token === $expectedToken) {
            Log::info('WhatsAppWebhook: vérification réussie');
            return response((string) $challenge, 200)
                ->header('Content-Type', 'text/plain');
        }

        Log::channel('security')->warning('WhatsAppWebhook: vérification échouée — token invalide', [
            'ip'             => $request->ip(),
            'mode'           => $mode,
            'token_received' => $token ? substr($token, 0, 8) . '...' : null,
        ]);

        return $this->unauthorized('Token de vérification invalide');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Réception des événements (POST)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Traiter les événements entrants du webhook WhatsApp.
     *
     * Les événements peuvent être :
     *  - messages  : texte, image, audio, document, localisation, bouton…
     *  - statuses  : sent, delivered, read, failed
     *
     * Meta attend une réponse 200 immédiate ; le traitement lourd doit être
     * délégué à des Jobs asynchrones.
     */
    public function handle(Request $request): JsonResponse
    {
        // HMAC-SHA256 signature validation (skip if app_secret not configured)
        $rawBody = $request->getContent();
        if (!$this->validateSignature($rawBody, $request->header('X-Hub-Signature-256', ''))) {
            Log::channel('security')->warning('WhatsAppWebhook: signature invalide', [
                'ip' => $request->ip(),
            ]);
            return response()->json(['success' => false, 'message' => 'Signature invalide'], 403);
        }

        $payload = $request->all();

        Log::debug('WhatsAppWebhook: payload reçu', [
            'ip'     => $request->ip(),
            'object' => $payload['object'] ?? null,
        ]);

        // Sanity check — Meta envoie toujours "whatsapp_business_account"
        if (($payload['object'] ?? null) !== 'whatsapp_business_account') {
            Log::warning('WhatsAppWebhook: object inattendu', ['object' => $payload['object'] ?? null]);
            return $this->success([], 'Ignored');
        }

        foreach ($payload['entry'] ?? [] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                if (($change['field'] ?? null) !== 'messages') {
                    continue;
                }

                $value = $change['value'] ?? [];

                $this->processMessages($value['messages'] ?? []);
                $this->processStatuses($value['statuses'] ?? []);
            }
        }

        // Meta exige un 200 rapide — répondre systématiquement
        return $this->success([], 'EVENT_RECEIVED');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Traitement des messages entrants
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Traiter les messages entrants.
     *
     * Actuellement, les messages sont loggés ; vous pouvez étendre cette méthode
     * pour dispatcher un Job ou déclencher une réponse automatique.
     *
     * @param  array<int, array<string, mixed>>  $messages
     */
    private function processMessages(array $messages): void
    {
        foreach ($messages as $message) {
            $type  = $message['type']  ?? 'unknown';
            $from  = $message['from']  ?? null;
            $msgId = $message['id']    ?? null;

            $context = [
                'message_id' => $msgId,
                'from'       => $from,
                'type'       => $type,
                'timestamp'  => $message['timestamp'] ?? null,
            ];

            match ($type) {
                'text'     => $this->handleTextMessage($message, $context),
                'image',
                'audio',
                'video',
                'document' => $this->handleMediaMessage($message, $context),
                'button'   => $this->handleButtonReply($message, $context),
                'location' => $this->handleLocation($message, $context),
                default    => Log::info("WhatsAppWebhook: message de type '{$type}' non géré", $context),
            };
        }
    }

    /**
     * @param  array<string, mixed>  $message
     * @param  array<string, mixed>  $context
     */
    private function handleTextMessage(array $message, array $context): void
    {
        $body = $message['text']['body'] ?? '';

        Log::info('WhatsAppWebhook: message texte reçu', array_merge($context, [
            'body_preview' => mb_substr($body, 0, 100),
        ]));

        // Planned: dispatcher un Job pour traiter le message texte
        // dispatch(new HandleIncomingWhatsAppMessage($message));
    }

    /**
     * @param  array<string, mixed>  $message
     * @param  array<string, mixed>  $context
     */
    private function handleMediaMessage(array $message, array $context): void
    {
        Log::info('WhatsAppWebhook: message média reçu', array_merge($context, [
            'media_type' => $message['type'] ?? null,
        ]));

        // Planned: dispatcher un Job pour télécharger et traiter le média
        // dispatch(new HandleIncomingWhatsAppMedia($message));
    }

    /**
     * @param  array<string, mixed>  $message
     * @param  array<string, mixed>  $context
     */
    private function handleButtonReply(array $message, array $context): void
    {
        $buttonId    = $message['button']['payload'] ?? null;
        $buttonTitle = $message['button']['text']    ?? null;

        Log::info('WhatsAppWebhook: réponse bouton reçue', array_merge($context, [
            'button_id'    => $buttonId,
            'button_title' => $buttonTitle,
        ]));

        // Planned: traiter les réponses aux templates avec boutons CTA
        // dispatch(new HandleWhatsAppButtonReply($message));
    }

    /**
     * @param  array<string, mixed>  $message
     * @param  array<string, mixed>  $context
     */
    private function handleLocation(array $message, array $context): void
    {
        $lat  = $message['location']['latitude']  ?? null;
        $lng  = $message['location']['longitude'] ?? null;

        Log::info('WhatsAppWebhook: localisation reçue', array_merge($context, [
            'latitude'  => $lat,
            'longitude' => $lng,
        ]));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Traitement des mises à jour de statut
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Traiter les mises à jour de statut (sent, delivered, read, failed).
     *
     * @param  array<int, array<string, mixed>>  $statuses
     */
    private function processStatuses(array $statuses): void
    {
        foreach ($statuses as $status) {
            $msgId  = $status['id']           ?? null;
            $state  = $status['status']       ?? null;
            $phone  = $status['recipient_id'] ?? null;

            $context = [
                'message_id'   => $msgId,
                'status'       => $state,
                'recipient'    => $phone,
                'timestamp'    => $status['timestamp'] ?? null,
            ];

            match ($state) {
                'sent'      => Log::debug('WhatsAppWebhook: message envoyé', $context),
                'delivered' => Log::debug('WhatsAppWebhook: message remis', $context),
                'read'      => Log::debug('WhatsAppWebhook: message lu', $context),
                'failed'    => $this->handleFailedStatus($status, $context),
                default     => Log::debug("WhatsAppWebhook: statut '{$state}' non géré", $context),
            };
        }
    }

    /**
     * @param  array<string, mixed>  $status
     * @param  array<string, mixed>  $context
     */
    private function handleFailedStatus(array $status, array $context): void
    {
        $errors = $status['errors'] ?? [];

        Log::warning('WhatsAppWebhook: échec de livraison message', array_merge($context, [
            'errors' => $errors,
        ]));

        // Planned: marquer le message comme échoué en base de données
        // dispatch(new HandleWhatsAppDeliveryFailure($status));
    }

    /**
     * Valider la signature HMAC-SHA256 envoyée par Meta.
     *
     * Si app_secret n'est pas configuré, on laisse passer (rétro-compatibilité).
     * Format attendu : X-Hub-Signature-256: sha256=<hex>
     */
    private function validateSignature(string $rawBody, string $signature): bool
    {
        $secret = config('services.whatsapp_cloud.app_secret');
        if (!$secret) {
            // En production, rejeter tout webhook sans secret configuré
            if (config('app.env') === 'production') {
                return false;
            }
            return true;
        }
        if (!str_starts_with($signature, 'sha256=')) {
            return false;
        }
        return hash_equals('sha256=' . hash_hmac('sha256', $rawBody, $secret), $signature);
    }
}
