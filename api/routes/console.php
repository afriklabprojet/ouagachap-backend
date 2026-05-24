<?php

use App\Jobs\AutoDispatchPendingOrdersJob;
use App\Jobs\CleanupExpiredOrdersJob;
use App\Jobs\GenerateDailyReportJob;
use App\Jobs\ReassignStuckOrderJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Tasks - OUAGA CHAP
|--------------------------------------------------------------------------
*/

// Dispatcher automatiquement les commandes PENDING sans coursier (toutes les 2 min)
Schedule::job(new AutoDispatchPendingOrdersJob)->everyTwoMinutes();

// Nettoyer les commandes expirées toutes les heures
Schedule::job(new CleanupExpiredOrdersJob)->hourly();

// Réassigner les commandes bloquées (ASSIGNED/ACCEPTED > 10 min sans activité)
Schedule::job(new ReassignStuckOrderJob)->everyFiveMinutes();

// Générer le rapport quotidien à 1h du matin
Schedule::job(new GenerateDailyReportJob)->dailyAt('01:00');

// Nettoyer les tokens expirés une fois par jour
Schedule::command('sanctum:prune-expired --hours=24')->daily();

// Nettoyer les fichiers temporaires
Schedule::command('cache:prune-stale-tags')->hourly();
