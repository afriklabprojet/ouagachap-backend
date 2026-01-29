<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CleanupExpiredOrdersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        // Annuler les commandes en attente depuis plus de 24h
        $expiredOrders = Order::where('status', 'pending')
            ->where('created_at', '<', now()->subHours(24))
            ->get();

        foreach ($expiredOrders as $order) {
            $order->update([
                'status' => 'cancelled',
                'cancellation_reason' => 'Commande expirée - pas de paiement reçu',
            ]);

            Log::info("Commande {$order->id} expirée et annulée");
        }

        Log::info("Nettoyage terminé: {$expiredOrders->count()} commandes annulées");
    }
}
