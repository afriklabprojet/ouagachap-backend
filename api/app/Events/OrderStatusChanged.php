<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Événement émis quand le statut d'une commande change.
 * Diffusé en temps réel au client et au coursier via WebSocket.
 */
class OrderStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Order $order,
        public string $previousStatus,
        public string $newStatus
    ) {}

    /**
     * Canaux de diffusion — le canal de la commande + le canal du coursier
     */
    public function broadcastOn(): array
    {
        $channels = [
            // Singular channel: subscribed by the Flutter client
            new PrivateChannel('order.' . $this->order->id),
            // Plural channel: kept for backward compatibility
            new PrivateChannel('orders.' . $this->order->id),
        ];

        // Notifier aussi le coursier assigné
        if ($this->order->courier_id) {
            $channels[] = new PrivateChannel('courier.' . $this->order->courier_id . '.orders');
        }

        // Notifier aussi le client
        if ($this->order->client_id) {
            $channels[] = new PrivateChannel('App.Models.User.' . $this->order->client_id);
        }

        // Notifier aussi le destinataire s'il utilise l'app (commandes entrantes)
        if ($this->order->recipient_user_id) {
            $channels[] = new PrivateChannel('App.Models.User.' . $this->order->recipient_user_id);
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'order.status_changed';
    }

    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'previous_status' => $this->previousStatus,
            'new_status' => $this->newStatus,
            'updated_at' => $this->order->updated_at?->toIso8601String(),
        ];
    }
}
