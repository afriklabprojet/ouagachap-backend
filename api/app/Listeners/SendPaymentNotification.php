<?php

namespace App\Listeners;

use App\Events\PaymentCompleted;
use App\Services\PushNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendPaymentNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        protected PushNotificationService $pushService
    ) {}

    public function handle(PaymentCompleted $event): void
    {
        // Notify client that payment was received
        $this->pushService->notifyPaymentReceived($event->order);
    }
}
