<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\SmartDispatcherService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Tente d'assigner immédiatement un coursier à une nouvelle commande.
 * Dispatché dès la création de la commande (sans délai).
 * L'AutoDispatchPendingOrdersJob sert de filet si ce Job échoue ou si aucun
 * coursier n'est disponible au moment de la création.
 */
class DispatchNewOrderJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(
        public readonly Order $order,
    ) {}

    public function handle(SmartDispatcherService $dispatcher): void
    {
        $order = $this->order->fresh();

        if (! $order || ! $order->isPending()) {
            Log::info("[DispatchNewOrder] Commande #{$this->order->id} non dispatchable (statut: {$order?->status->value}).");

            return;
        }

        $result = $dispatcher->dispatchOrder($order);

        if ($result['success']) {
            Log::info("[DispatchNewOrder] Commande #{$order->id} assignée au coursier #{$result['courier']->id}.");
        } else {
            Log::info("[DispatchNewOrder] Commande #{$order->id} non assignée: {$result['message']}. L'AutoDispatch reprendra.");
        }
    }
}
