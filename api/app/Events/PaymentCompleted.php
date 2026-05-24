<?php

namespace App\Events;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Événement émis quand un paiement est complété avec succès.
 */
class PaymentCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Payment $payment,
        public ?Order $order = null
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('App.Models.User.' . $this->payment->user_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'payment.completed';
    }

    public function broadcastWith(): array
    {
        return [
            'payment_id' => $this->payment->id,
            'transaction_id' => $this->payment->transaction_id,
            'amount' => $this->payment->amount,
            'method' => $this->payment->method,
            'order_id' => $this->order?->id,
            'order_number' => $this->order?->order_number,
            'paid_at' => $this->payment->paid_at?->toIso8601String(),
        ];
    }
}
