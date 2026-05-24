<?php

namespace App\Observers;

use App\Models\Zone;
use App\Services\CacheService;

/**
 * Observer pour invalider le cache des zones.
 * 
 * Déclenché automatiquement lors de :
 * - Création d'une zone
 * - Modification d'une zone (nom, prix, actif/inactif)
 * - Suppression d'une zone
 * 
 * Impact : Garantit que le cache est toujours à jour
 */
class ZoneObserver
{
    public function __construct(
        private CacheService $cacheService
    ) {}
    
    /**
     * Appelé après création d'une zone.
     */
    public function created(Zone $zone): void
    {
        $this->cacheService->clearZonesCache();
    }
    
    /**
     * Appelé après modification d'une zone.
     */
    public function updated(Zone $zone): void
    {
        $this->cacheService->clearZonesCache();
    }
    
    /**
     * Appelé après suppression d'une zone.
     */
    public function deleted(Zone $zone): void
    {
        $this->cacheService->clearZonesCache();
    }
    
    /**
     * Appelé après restauration d'une zone (soft delete).
     */
    public function restored(Zone $zone): void
    {
        $this->cacheService->clearZonesCache();
    }
}
