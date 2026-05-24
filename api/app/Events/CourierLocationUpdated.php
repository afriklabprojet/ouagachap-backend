<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Émis quand un coursier met à jour sa position GPS.
 * Diffusé sur le canal de la commande active si elle existe.
 */
class CourierLocationUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public User $courier,
        public float $latitude,
        public float $longitude,
        public ?string $orderId = null
    ) {}

    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('courier.' . $this->courier->id . '.location'),
        ];

        if ($this->orderId !== null) {
            // Singular channel: subscribed by the Flutter client
            $channels[] = new PrivateChannel('order.' . $this->orderId);
            // Plural channel: kept for backward compatibility
            $channels[] = new PrivateChannel('orders.' . $this->orderId);
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'location.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'courier_id'   => $this->courier->id,
            'courier_name' => $this->courier->name,
            'latitude'     => $this->latitude,
            'longitude'    => $this->longitude,
            'heading'      => $this->courier->heading ?? null,
            'speed'        => $this->courier->speed ?? null,
            'timestamp'    => now()->toIso8601String(),
        ];
    }
}
