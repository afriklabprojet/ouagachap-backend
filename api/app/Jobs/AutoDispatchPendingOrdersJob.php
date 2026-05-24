<?php

namespace App\Jobs;

use App\Services\SmartDispatcherService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Traite automatiquement toutes les commandes PENDING sans coursier assigné.
 * Planifié toutes les 2 minutes via le scheduler Laravel.
 */
class AutoDispatchPendingOrdersJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    /** Nombre de tentatives max avant abandon */
    public int $tries = 1;

    /** Timeout en secondes (2 min max pour éviter les overlaps) */
    public int $timeout = 110;

    public function handle(SmartDispatcherService $dispatcher): void
    {
        Log::info('[AutoDispatch] Démarrage du traitement des commandes en attente.');

        $result = $dispatcher->autoDispatchPending();

        Log::info('[AutoDispatch] Terminé.', [
            'dispatched' => $result['dispatched'] ?? 0,
            'failed'     => $result['failed'] ?? 0,
            'skipped'    => $result['skipped'] ?? 0,
        ]);
    }
}
