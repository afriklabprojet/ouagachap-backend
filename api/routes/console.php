<?php

use App\Jobs\AutoDispatchPendingOrdersJob;
use App\Jobs\CleanupExpiredOrdersJob;
use App\Jobs\DetectStaticCourierJob;
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

$sentryEnabled = (bool) config('sentry.dsn');

$schedule = function (object $entry, string $monitor) use ($sentryEnabled): object {
    return $sentryEnabled ? $entry->sentryMonitor($monitor) : $entry;
};

// Dispatcher automatiquement les commandes PENDING sans coursier (toutes les 2 min)
$schedule(
    Schedule::job(new AutoDispatchPendingOrdersJob)->everyTwoMinutes(),
    'ouagachap-auto-dispatch'
);

// Nettoyer les commandes expirées toutes les heures
$schedule(
    Schedule::job(new CleanupExpiredOrdersJob)->hourly(),
    'ouagachap-cleanup-expired-orders'
);

// Réassigner les commandes bloquées (ASSIGNED/ACCEPTED > 10 min sans activité)
$schedule(
    Schedule::job(new ReassignStuckOrderJob)->everyFiveMinutes(),
    'ouagachap-reassign-stuck-orders'
);

// Détecter les coursiers statiques (sans mouvement pendant une livraison active)
$schedule(
    Schedule::job(new DetectStaticCourierJob)->everyFiveMinutes(),
    'ouagachap-detect-static-couriers'
);

// Générer le rapport quotidien à 1h du matin
$schedule(
    Schedule::job(new GenerateDailyReportJob)->dailyAt('01:00'),
    'ouagachap-daily-report'
);

// Nettoyer les tokens expirés une fois par jour
$schedule(
    Schedule::command('sanctum:prune-expired --hours=24')->daily(),
    'ouagachap-prune-expired-tokens'
);

// Nettoyer les fichiers temporaires
$schedule(
    Schedule::command('cache:prune-stale-tags')->hourly(),
    'ouagachap-prune-stale-cache-tags'
);
