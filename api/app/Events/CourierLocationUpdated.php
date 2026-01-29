<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Événement émis quand un coursier met à jour sa position GPS
 * Diffusé aux clients qui ont une commande active avec ce coursier
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

    /**
     * Canaux de diffusion
     */
    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel("courier.{$this->courier->id}.location"),
        ];

        // Si lié à une commande spécifique
        if ($this->orderId) {
            $channels[] = new PrivateChannel("orders.{$this->orderId}");
        }

        return $channels;
    }

    /**
     * Nom de l'événement côté client
     */
    public function broadcastAs(): string
    {
        return 'location.updated';
    }

    /**
     * Données diffusées
     */
    public function broadcastWith(): array
    {
        return [
            'courier_id' => $this->courier->id,
            'courier_name' => $this->courier->name,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
