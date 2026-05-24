<?php

namespace App\Listeners;

use App\Events\OrderStatusChanged;
use App\Services\PushNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Envoie une notification push adaptée au changement de statut de la commande.
 */
class SendOrderStatusNotification implements ShouldQueue
{
    public function __construct(
        private PushNotificationService $pushService
    ) {}

    public function handle(OrderStatusChanged $event): void
    {
        $order = $event->order;
        $newStatus = $event->newStatus;

        try {
            match ($newStatus) {
                'picked_up' => $this->pushService->notifyOrderPickedUp($order),
                'delivered' => $this->pushService->notifyOrderDelivered($order),
                'cancelled' => $this->pushService->notifyOrderCancelled($order),
                default => null,
            };
        } catch (\Exception $e) {
            Log::warning('Failed to send order status notification', [
                'order_id' => $order->id,
                'status' => $newStatus,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
