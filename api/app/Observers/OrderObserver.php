<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\CacheService;

/**
 * Observer pour invalider le cache des statistiques.
 * 
 * Déclenché automatiquement lors de :
 * - Création d'une commande (total_orders, pending_orders)
 * - Changement de status (pending_orders, today_revenue)
 * 
 * Impact : Statistiques admin temps réel avec cache intelligent
 */
class OrderObserver
{
    public function __construct(
        private CacheService $cacheService
    ) {}
    
    /**
     * Appelé après création d'une commande.
     */
    public function created(Order $order): void
    {
        // Invalider stats (nouveau total_orders, pending_orders)
        $this->cacheService->clearStatsCache();
    }
    
    /**
     * Appelé après modification d'une commande.
     * 
     * Important pour :
     * - Changement status (pending → accepted → delivering → completed)
     * - Ajout de commission
     */
    public function updated(Order $order): void
    {
        // Invalider stats si le status a changé
        if ($order->isDirty('status')) {
            $this->cacheService->clearStatsCache();
        }
    }
}
