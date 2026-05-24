<?php

namespace App\Logging;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;

/**
 * Monolog handler pour Better Stack (Logtail).
 *
 * Envoie les logs vers https://in.logs.betterstack.com via HTTP.
 * Ne nécessite pas de dépendance externe — utilise file_get_contents
 * avec un stream context (disponible partout où PHP est installé).
 *
 * Configuration .env :
 *   LOGTAIL_SOURCE_TOKEN=<token obtenu sur logs.betterstack.com>
 *   LOGTAIL_LEVEL=debug        # niveau minimum (debug en staging, warning en prod)
 *
 * Activer dans LOG_STACK :
 *   LOG_STACK=daily,sentry,logtail    (staging)
 *   LOG_STACK=daily,sentry,logtail    (production)
 */
class BetterstackHandler extends AbstractProcessingHandler
{
    private const ENDPOINT = 'https://in.logs.betterstack.com';

    private const TIMEOUT = 3;

    public function __construct(
        private readonly string $sourceToken,
        int|string|Level $level = Level::Debug,
        bool $bubble = true,
    ) {
        parent::__construct($level, $bubble);
    }

    protected function write(LogRecord $record): void
    {
        if (empty($this->sourceToken)) {
            return;
        }

        $payload = $this->buildPayload($record);

        $this->send($payload);
    }

    private function buildPayload(LogRecord $record): string
    {
        $data = [
            'dt'          => $record->datetime->format(\DateTimeInterface::RFC3339_EXTENDED),
            'level'       => $record->level->getName(),
            'level_value' => $record->level->value,
            'message'     => $record->message,
            'channel'     => $record->channel,
            'app'         => config('app.name', 'OUAGA CHAP'),
            'env'         => config('app.env', 'production'),
        ];

        // Aplatir le contexte (user_id, order_id, etc.)
        if (! empty($record->context)) {
            foreach ($record->context as $key => $value) {
                $data["ctx_{$key}"] = is_scalar($value) ? $value : json_encode($value);
            }
        }

        // Aplatir les extras Monolog (memory_usage, etc.)
        if (! empty($record->extra)) {
            foreach ($record->extra as $key => $value) {
                $data["extra_{$key}"] = is_scalar($value) ? $value : json_encode($value);
            }
        }

        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function send(string $payload): void
    {
        try {
            $context = stream_context_create([
                'http' => [
                    'method'        => 'POST',
                    'header'        => implode("\r\n", [
                        'Content-Type: application/json',
                        'Authorization: Bearer ' . $this->sourceToken,
                        'Content-Length: ' . strlen($payload),
                    ]),
                    'content'       => $payload,
                    'timeout'       => self::TIMEOUT,
                    'ignore_errors' => true,
                ],
                'ssl' => [
                    'verify_peer'      => true,
                    'verify_peer_name' => true,
                ],
            ]);

            @file_get_contents(self::ENDPOINT, false, $context);
        } catch (\Throwable) {
            // Logging failures must never break the application
        }
    }
}
