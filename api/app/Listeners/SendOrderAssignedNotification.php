<?php

namespace App\Listeners;

use App\Events\OrderAssigned;
use App\Services\PushNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendOrderAssignedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        protected PushNotificationService $pushService
    ) {}

    public function handle(OrderAssigned $event): void
    {
        // Notify client that a courier has been assigned
        $this->pushService->notifyOrderAssigned($event->order);
    }
}
