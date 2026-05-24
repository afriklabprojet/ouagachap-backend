<?php

namespace App\Jobs;

use App\Enums\NotificationType;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public User $user,
        public NotificationType $type,
        public array $data = [],
        public ?string $customTitle = null,
        public ?string $customMessage = null
    ) {}

    public function handle(NotificationService $service): void
    {
        $service->send(
            $this->user,
            $this->type,
            $this->data,
            $this->customTitle,
            $this->customMessage
        );
    }

    public function failed(\Throwable $exception): void
    {
        \Log::error("Failed to send notification to user {$this->user->id}: " . $exception->getMessage());
    }
}
