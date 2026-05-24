<?php

namespace App\Listeners;

use App\Events\OrderAssigned;
use App\Services\PushNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Envoie une notification push au coursier quand une commande lui est assignée,
 * et notifie le client que son coursier est en route.
 */
class SendOrderAssignedNotification implements ShouldQueue
{
    public function __construct(
        private PushNotificationService $pushService
    ) {}

    public function handle(OrderAssigned $event): void
    {
        $order = $event->order;

        try {
            // Notifier le coursier avec la nouvelle commande (alerte fullScreenIntent)
            if ($order->courier) {
                $this->pushService->notifyNewOrderAvailable($order, $order->courier);
            }

            // Notifier le client que son coursier est assigné
            $this->pushService->notifyOrderAssigned($order);
        } catch (\Exception $e) {
            Log::warning('Failed to send order assigned notification', [
                'order_id' => $order->id,
                'courier_id' => $order->courier_id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
