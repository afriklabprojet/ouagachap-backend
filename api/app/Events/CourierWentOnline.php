<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Événement émis quand un coursier change de disponibilité (en ligne / hors ligne).
 */
class CourierWentOnline implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public User $courier,
        public string $status // 'online' | 'offline'
    ) {}

    /**
     * Alias for $status property.
     */
    public function __get($name)
    {
        if ($name === 'action') {
            return $this->status;
        }
    }

    public function broadcastOn(): array
    {
        // Diffuser sur le canal admin pour les métriques en direct
        return [
            new Channel('admin.courier-availability'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'courier.availability_changed';
    }

    public function broadcastWith(): array
    {
        return [
            'courier_id' => $this->courier->id,
            'courier_name' => $this->courier->name,
            'status' => $this->status,
            'is_available' => $this->courier->is_available,
            'updated_at' => now()->toIso8601String(),
        ];
    }
}
