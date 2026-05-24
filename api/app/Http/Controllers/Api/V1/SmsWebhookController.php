<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\SmsDeliveryReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SmsWebhookController extends BaseController
{
    /**
     * Traiter les accusés de réception SMS Infobip.
     *
     * Infobip POST un JSON avec un tableau "results" contenant les rapports
     * de livraison. Chaque résultat contient messageId, to, sentAt, doneAt,
     * status (groupName, name, description), error, price, smsCount, etc.
     *
     * @see https://www.infobip.com/docs/api/channels/sms/sms-messaging/logs-and-status-reports/receive-outbound-sms-message-report
     */
    public function handle(Request $request): JsonResponse
    {
        // HMAC signature validation (skip if signing_secret not configured — rétro-compatibilité)
        $rawBody = $request->getContent();
        if (!$this->validateSignature($rawBody, $request->header('X-Infobip-Signature', ''))) {
            Log::channel('security')->warning('SmsWebhook: signature invalide', [
                'ip' => $request->ip(),
            ]);
            return response()->json(['success' => false, 'message' => 'Signature invalide'], 403);
        }

        $results = $request->input('results', []);

        if (empty($results)) {
            Log::warning('SmsWebhook: payload vide reçu', [
                'ip'   => $request->ip(),
                'body' => $request->getContent(),
            ]);
            return $this->error('Payload vide', 400);
        }

        // Valider le callback prefix pour s'assurer que c'est bien notre requête
        $expectedPrefix = config('services.infobip.callback_prefix', '');

        $processed = 0;
        $skipped   = 0;

        foreach ($results as $result) {
            $messageId = $result['messageId'] ?? null;
            if (!$messageId) {
                $skipped++;
                continue;
            }

            // Vérifier le callback prefix si configuré
            $callbackData = $result['callbackData'] ?? '';
            if ($expectedPrefix && !str_starts_with($callbackData, $expectedPrefix)) {
                Log::warning('SmsWebhook: callback prefix invalide', [
                    'messageId'    => $messageId,
                    'callbackData' => $callbackData,
                    'expected'     => $expectedPrefix,
                ]);
                $skipped++;
                continue;
            }

            $statusGroup = $result['status']['groupName'] ?? 'UNKNOWN';
            $statusName  = $result['status']['name'] ?? 'UNKNOWN';

            // Upsert : mettre à jour si le messageId existe déjà (delivery en 2 étapes PENDING → DELIVERED)
            SmsDeliveryReport::updateOrCreate(
                ['message_id' => $messageId],
                [
                    'phone'              => $result['to'] ?? '',
                    'sender'             => $result['from'] ?? null,
                    'status_group'       => $statusGroup,
                    'status_name'        => $statusName,
                    'status_description' => $result['status']['description'] ?? null,
                    'error_code'         => $result['error']['id'] ?? 0,
                    'error_name'         => $result['error']['name'] ?? null,
                    'error_description'  => $result['error']['description'] ?? null,
                    'price'              => $result['price']['pricePerMessage'] ?? 0,
                    'currency'           => $result['price']['currency'] ?? 'EUR',
                    'callback_data'      => $callbackData,
                    'sent_at'            => isset($result['sentAt']) ? \Carbon\Carbon::parse($result['sentAt']) : null,
                    'done_at'            => isset($result['doneAt']) ? \Carbon\Carbon::parse($result['doneAt']) : null,
                    'sms_count'          => $result['smsCount'] ?? 1,
                ]
            );

            $processed++;

            // Log les échecs pour monitoring
            if (in_array($statusGroup, ['REJECTED', 'UNDELIVERABLE', 'EXPIRED'])) {
                Log::warning('SmsWebhook: échec livraison SMS', [
                    'messageId' => $messageId,
                    'phone'     => substr($result['to'] ?? '', 0, 7) . '****',
                    'status'    => $statusGroup,
                    'error'     => $result['error']['name'] ?? 'N/A',
                ]);
            }
        }

        Log::info('SmsWebhook: traitement terminé', [
            'processed' => $processed,
            'skipped'   => $skipped,
            'total'     => count($results),
        ]);

        return $this->success(null, 'OK');
    }

    /**
     * Valider la signature HMAC Infobip.
     *
     * Si signing_secret n'est pas configuré, on laisse passer (rétro-compatibilité).
     * Header : X-Infobip-Signature
     */
    private function validateSignature(string $rawBody, string $signature): bool
    {
        $secret = config('services.infobip.signing_secret');
        if (!$secret) {
            // En production, rejeter tout webhook sans secret configuré
            if (config('app.env') === 'production') {
                return false;
            }
            return true;
        }
        if ($signature === '') {
            return false;
        }
        return hash_equals(hash_hmac('sha256', $rawBody, $secret), $signature);
    }
}
