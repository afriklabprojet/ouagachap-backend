<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Services\PushNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendOrderCreatedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        protected PushNotificationService $pushService
    ) {}

    public function handle(OrderCreated $event): void
    {
        // Notify client that order was created
        $this->pushService->notifyOrderCreated($event->order);

        // Broadcast to available couriers
        $this->pushService->broadcastToAvailableCouriers($event->order);
    }
}
