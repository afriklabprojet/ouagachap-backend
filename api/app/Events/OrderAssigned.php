<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Événement émis quand une commande est assignée à un coursier.
 * Diffusé en temps réel au coursier via WebSocket.
 */
class OrderAssigned implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Order $order
    ) {}

    /**
     * Diffuser sur le canal privé du coursier assigné
     */
    public function broadcastOn(): array
    {
        $channels = [];

        if ($this->order->courier_id) {
            $channels[] = new PrivateChannel('courier.' . $this->order->courier_id . '.orders');
        }

        // Notifier aussi le client
        if ($this->order->client_id) {
            $channels[] = new PrivateChannel('App.Models.User.' . $this->order->client_id);
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'order.assigned';
    }

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
            'client_name' => $this->order->client?->name,
            'client_phone' => $this->order->client?->phone,
            'created_at' => $this->order->created_at?->toIso8601String(),
        ];
    }
}
