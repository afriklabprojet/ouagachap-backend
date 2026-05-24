<?php

namespace App\Listeners;

use App\Events\PaymentCompleted;
use App\Services\PushNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Envoie des notifications push au client et au coursier quand un paiement est complété.
 */
class SendPaymentNotification implements ShouldQueue
{
    public function __construct(
        private PushNotificationService $pushService
    ) {}

    public function handle(PaymentCompleted $event): void
    {
        $payment = $event->payment;
        $order = $event->order;

        if (!$order) {
            return;
        }

        try {
            // Notifier le client du paiement reçu
            $this->pushService->notifyPaymentReceived($order);

            // Notifier le coursier de ses gains
            $this->pushService->notifyCourierEarnings($order);
        } catch (\Exception $e) {
            Log::warning('Failed to send payment notification', [
                'payment_id' => $payment->id,
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
