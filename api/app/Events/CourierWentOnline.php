<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CourierWentOnline implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public User $courier;
    public string $action; // 'online' ou 'offline'

    /**
     * Create a new event instance.
     */
    public function __construct(User $courier, string $action = 'online')
    {
        $this->courier = $courier;
        $this->action = $action;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('admin-notifications'),
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'courier_id' => $this->courier->id,
            'courier_name' => $this->courier->name ?? $this->courier->phone,
            'courier_phone' => $this->courier->phone,
            'vehicle_type' => $this->courier->vehicle_type,
            'action' => $this->action,
            'latitude' => $this->courier->current_latitude,
            'longitude' => $this->courier->current_longitude,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'courier.availability';
    }
}
