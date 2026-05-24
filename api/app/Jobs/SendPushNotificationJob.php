<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\PushNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job pour l'envoi asynchrone de notifications push.
 * Découple les appels Firebase de la latence HTTP.
 */
class SendPushNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;
    public int $timeout = 30;

    public function __construct(
        private int $userId,
        private string $title,
        private string $body,
        private array $data = []
    ) {}

    public function handle(PushNotificationService $pushService): void
    {
        $user = User::find($this->userId);

        if (!$user || !$user->fcm_token) {
            return;
        }

        $pushService->sendToUser($user, $this->title, $this->body, $this->data);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SendPushNotificationJob failed', [
            'user_id' => $this->userId,
            'title' => $this->title,
            'error' => $exception->getMessage(),
        ]);
    }
}
