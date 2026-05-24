<?php

namespace App\Jobs;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Services\SmsService;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Annule les commandes en attente depuis trop longtemps (>30 minutes sans coursier).
 */
class CleanupExpiredOrdersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 120;

    public function handle(): void
    {
        $cutoff = now()->subMinutes(30);

        $expired = Order::where('status', OrderStatus::PENDING)
            ->whereNull('courier_id')
            ->where('created_at', '<', $cutoff)
            ->get();

        $count = 0;
        foreach ($expired as $order) {
            $cancelled = $order->cancel('Annulation automatique : aucun coursier disponible dans les délais');

            if ($cancelled) {
                $count++;

                // Notifier le client par SMS
                try {
                    if ($order->client?->phone) {
                        $smsService = app(SmsService::class);
                        $smsService->send(
                            $order->client->phone,
                            "OUAGA CHAP : Votre commande #{$order->order_number} a été annulée, aucun coursier disponible. Veuillez réessayer."
                        );
                    }
                } catch (\Exception $e) {
                    Log::warning('CleanupExpiredOrdersJob: échec envoi SMS', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            } else {
                Log::warning('CleanupExpiredOrdersJob: transition échouée', ['order_id' => $order->id]);
            }
        }

        if ($count > 0) {
            Log::info("CleanupExpiredOrdersJob: {$count} commande(s) expirée(s) annulée(s)");
        }
    }
}
