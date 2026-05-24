<?php

namespace App\Listeners;

use App\Events\NewOrderAvailable;
use App\Events\OrderCreated;
use App\Services\CourierMatchingService;
use App\Services\PushNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Envoie une notification push au client quand sa commande est créée,
 * et diffuse l'event WebSocket aux coursiers disponibles à proximité.
 */
class SendOrderCreatedNotification implements ShouldQueue
{
    public function __construct(
        private PushNotificationService $pushService,
        private CourierMatchingService $courierMatchingService,
    ) {}

    public function handle(OrderCreated $event): void
    {
        $order = $event->order;

        // 1. Notifier le client (FCM push)
        try {
            $this->pushService->notifyOrderCreated($order);
        } catch (\Exception $e) {
            Log::warning('Failed to send order created notification', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }

        // 2. Diffuser l'event WebSocket + FCM aux coursiers proches
        if (! $order->pickup_latitude || ! $order->pickup_longitude) {
            return;
        }

        try {
            $couriers = $this->courierMatchingService->getSmartMatchedCouriers(
                (float) $order->pickup_latitude,
                (float) $order->pickup_longitude,
                ['is_large' => false, 'is_fragile' => false, 'order_type' => 'standard', 'weight' => 0],
                radiusKm: 10,
                limit: 20
            );

            $courierIds = $couriers->pluck('id')->map(fn ($id) => (int) $id)->all();

            if (! empty($courierIds)) {
                event(new NewOrderAvailable($order, $courierIds));
                $this->pushService->broadcastToAvailableCouriers($order);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to broadcast new order to couriers', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
