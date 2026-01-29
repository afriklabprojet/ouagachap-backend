<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Événement émis pour les nouvelles commandes disponibles
 * Diffusé aux coursiers disponibles à proximité
 */
class NewOrderAvailable implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Order $order,
        public array $courierIds = []
    ) {}

    /**
     * Canaux de diffusion - un par coursier ciblé
     */
    public function broadcastOn(): array
    {
        return collect($this->courierIds)
            ->map(fn ($id) => new PrivateChannel("courier.{$id}.orders"))
            ->toArray();
    }

    /**
     * Nom de l'événement côté client
     */
    public function broadcastAs(): string
    {
        return 'order.available';
    }

    /**
     * Données diffusées
     */
    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'pickup_address' => $this->order->pickup_address,
            'dropoff_address' => $this->order->dropoff_address,
            'distance_km' => $this->order->distance_km,
            'total_price' => $this->order->total_price,
            'courier_earnings' => $this->order->courier_earnings,
            'pickup_location' => [
                'latitude' => $this->order->pickup_latitude,
                'longitude' => $this->order->pickup_longitude,
            ],
            'created_at' => $this->order->created_at->toIso8601String(),
        ];
    }
}
