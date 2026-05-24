<?php

namespace App\Listeners;

use App\Events\OrderMessageSent;
use App\Models\User;
use App\Services\PushNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendOrderMessageNotification implements ShouldQueue
{
    public function __construct(
        private PushNotificationService $pushService
    ) {}

    public function handle(OrderMessageSent $event): void
    {
        $message = $event->message;
        $order = $message->order;

        if (!$order) {
            return;
        }

        // Déterminer le destinataire (l'autre partie)
        $recipientId = $message->sender_type === 'client'
            ? $order->courier_id
            : $order->client_id;

        if (!$recipientId) {
            return;
        }

        $recipient = User::find($recipientId);

        if (!$recipient || !$recipient->fcm_token) {
            return;
        }

        $senderName = $message->sender?->name ?? ($message->sender_type === 'client' ? 'Client' : 'Coursier');
        $body = $message->message ?: '📷 Photo envoyée';

        try {
            $this->pushService->sendToUser(
                $recipient,
                "💬 Message de {$senderName}",
                mb_substr($body, 0, 200),
                [
                    'type' => 'order_chat_message',
                    'order_id' => (string) $order->id,
                    'message_id' => (string) $message->id,
                    'sender_type' => $message->sender_type,
                    'sender_name' => $senderName,
                ]
            );
        } catch (\Exception $e) {
            Log::warning('Failed to send chat notification', [
                'order_id' => $order->id,
                'message_id' => $message->id,
                'recipient_id' => $recipientId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
