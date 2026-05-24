<?php

namespace App\Jobs;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\WalletService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CreditCourierWalletJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public Order $order
    ) {}

    public function handle(WalletService $walletService): void
    {
        // Vérifier que la commande est bien livrée
        if ($this->order->status !== OrderStatus::DELIVERED) {
            Log::warning("Tentative de crédit pour commande non livrée: {$this->order->id}");
            return;
        }

        // Vérifier qu'un coursier est assigné
        if (!$this->order->courier) {
            Log::warning("Pas de coursier pour la commande: {$this->order->id}");
            return;
        }

        // Créditer le portefeuille du coursier
        $wallet = $walletService->creditCourierForDelivery(
            $this->order->courier,
            $this->order->courier_earnings
        );

        Log::info("Coursier {$this->order->courier->id} crédité de {$this->order->courier_earnings} FCFA pour commande {$this->order->id}");
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Failed to credit courier for order {$this->order->id}: " . $exception->getMessage());
    }
}
