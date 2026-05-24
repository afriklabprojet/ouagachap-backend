<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Événement émis quand une nouvelle commande est disponible pour les coursiers.
 * Diffusé en temps réel aux coursiers ciblés via WebSocket.
 */
class NewOrderAvailable implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  Order  $order
     * @param  int[]  $courierIds  IDs des coursiers ciblés
     */
    public function __construct(
        public Order $order,
        public array $courierIds = []
    ) {}

    /**
     * Diffuser sur les canaux privés des coursiers ciblés
     */
    public function broadcastOn(): array
    {
        return array_map(
            fn (int $courierId) => new PrivateChannel('courier.' . $courierId . '.orders'),
            $this->courierIds
        );
    }

    public function broadcastAs(): string
    {
        return 'order.available';
    }

    public function broadcastWith(): array
    {
        return [
            'order_id'        => $this->order->id,
            'order_number'    => $this->order->order_number,
            'pickup_address'  => $this->order->pickup_address,
            'dropoff_address' => $this->order->dropoff_address,
            'distance_km'     => $this->order->distance_km,
            'total_price'     => $this->order->total_price,
            'pickup_location' => [
                'latitude'  => $this->order->pickup_latitude,
                'longitude' => $this->order->pickup_longitude,
            ],
            'created_at'      => $this->order->created_at?->toIso8601String(),
        ];
    }
}
