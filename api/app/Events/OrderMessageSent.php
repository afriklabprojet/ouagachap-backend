<?php

namespace App\Events;

use App\Models\OrderMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderMessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public OrderMessage $message
    ) {}

    /**
     * Canal de diffusion pour le suivi de commande en temps réel
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('orders.' . $this->message->order_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'order_id' => $this->message->order_id,
            'sender_id' => $this->message->sender_id,
            'sender_type' => $this->message->sender_type,
            'sender_name' => $this->message->sender?->name,
            'message' => $this->message->message,
            'image_url' => $this->message->image_url,
            'created_at' => $this->message->created_at->toIso8601String(),
        ];
    }
}
