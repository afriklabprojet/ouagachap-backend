<?php

namespace App\Listeners;

use App\Enums\OrderStatus;
use App\Events\OrderStatusChanged;
use App\Services\PushNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendOrderStatusNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        protected PushNotificationService $pushService
    ) {}

    public function handle(OrderStatusChanged $event): void
    {
        $order = $event->order;
        $newStatus = $event->newStatus;

        match ($newStatus) {
            OrderStatus::PICKED_UP->value, 'picked_up' => $this->pushService->notifyOrderPickedUp($order),
            OrderStatus::DELIVERED->value, 'delivered' => $this->handleDelivered($order),
            OrderStatus::CANCELLED->value, 'cancelled' => $this->pushService->notifyOrderCancelled($order),
            default => null,
        };
    }

    protected function handleDelivered($order): void
    {
        // Notify client
        $this->pushService->notifyOrderDelivered($order);

        // Notify courier of earnings
        $this->pushService->notifyCourierEarnings($order);
    }
}
