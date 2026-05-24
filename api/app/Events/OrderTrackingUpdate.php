<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Émis à chaque mise à jour de position du coursier sur une commande active.
 * Contient la position courante, l'ETA calculé et la distance restante.
 *
 * Payload attendu par le client Flutter (LiveTrackingBloc._handleTrackingUpdate) :
 *   data.courier.latitude  / .longitude  — position temps réel
 *   data.eta_minutes        — estimation en minutes (calculé à 25 km/h)
 *   data.distance_remaining — distance restante en km
 *   data.order_status       — statut courant de la commande
 */
class OrderTrackingUpdate implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Order $order,
        public float $courierLatitude,
        public float $courierLongitude,
        public int   $etaMinutes,
        public float $distanceKm
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('order.' . $this->order->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'tracking.update';
    }

    public function broadcastWith(): array
    {
        return [
            'order_id'           => $this->order->id,
            'order_status'       => $this->order->status instanceof \BackedEnum
                ? $this->order->status->value
                : $this->order->status,
            'courier'            => [
                'id'        => $this->order->courier_id,
                'latitude'  => $this->courierLatitude,
                'longitude' => $this->courierLongitude,
                'timestamp' => now()->toIso8601String(),
            ],
            'eta_minutes'        => $this->etaMinutes,
            'distance_remaining' => $this->distanceKm,
            'updated_at'         => now()->toIso8601String(),
        ];
    }
}
