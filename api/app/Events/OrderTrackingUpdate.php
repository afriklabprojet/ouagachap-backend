<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Événement de tracking temps réel pour une commande
 * Combine position du coursier et ETA
 */
class OrderTrackingUpdate implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Order $order,
        public float $courierLatitude,
        public float $courierLongitude,
        public ?int $etaMinutes = null,
        public ?float $distanceRemaining = null
    ) {}

    /**
     * Canal de diffusion
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("orders.{$this->order->id}"),
        ];
    }

    /**
     * Nom de l'événement côté client
     */
    public function broadcastAs(): string
    {
        return 'tracking.update';
    }

    /**
     * Données diffusées
     */
    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->order->id,
            'order_status' => $this->order->status->value,
            'courier' => [
                'id' => $this->order->courier_id,
                'name' => $this->order->courier?->name,
                'phone' => $this->order->courier?->phone,
                'latitude' => $this->courierLatitude,
                'longitude' => $this->courierLongitude,
            ],
            'eta_minutes' => $this->etaMinutes,
            'distance_remaining_km' => $this->distanceRemaining,
            'destination' => [
                'latitude' => $this->order->dropoff_latitude,
                'longitude' => $this->order->dropoff_longitude,
            ],
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
