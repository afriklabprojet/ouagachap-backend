<?php

namespace App\Jobs;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Annule les commandes en attente depuis trop longtemps (>30 minutes sans coursier).
 */
class CleanupExpiredOrdersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 120;

    public function handle(SmsService $smsService): void
    {
        $cutoff = now()->subMinutes(30);

        $expiredIds = Order::where('status', OrderStatus::PENDING)
            ->whereNull('courier_id')
            ->where('created_at', '<', $cutoff)
            ->pluck('id');

        if ($expiredIds->isEmpty()) {
            return;
        }

        $count = 0;

        foreach ($expiredIds as $orderId) {
            // Lock row to prevent double-cancellation if job overlaps
            $cancelled = DB::transaction(function () use ($orderId) {
                $order = Order::with('client')
                    ->lockForUpdate()
                    ->find($orderId);

                if (!$order || $order->status !== OrderStatus::PENDING || $order->courier_id !== null) {
                    return null;
                }

                return $order->cancel('Annulation automatique : aucun coursier disponible dans les délais')
                    ? $order
                    : null;
            });

            if ($cancelled) {
                $count++;

                try {
                    if ($cancelled->client?->phone) {
                        $smsService->send(
                            $cancelled->client->phone,
                            "OUAGA CHAP : Votre commande #{$cancelled->order_number} a été annulée, aucun coursier disponible. Veuillez réessayer."
                        );
                    }
                } catch (\Exception $e) {
                    Log::warning('CleanupExpiredOrdersJob: échec envoi SMS', [
                        'order_id' => $orderId,
                        'error'    => $e->getMessage(),
                    ]);
                }
            } elseif ($cancelled === false) {
                Log::warning('CleanupExpiredOrdersJob: transition échouée', ['order_id' => $orderId]);
            }
        }

        if ($count > 0) {
            Log::info("CleanupExpiredOrdersJob: {$count} commande(s) expirée(s) annulée(s)");
        }
    }
}
