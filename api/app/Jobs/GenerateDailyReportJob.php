<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\User;
use App\Enums\OrderStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Génère et logue un rapport quotidien des statistiques clés.
 */
class GenerateDailyReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 60;

    public function handle(): void
    {
        $yesterday = now()->subDay()->startOfDay();
        $endOfYesterday = now()->subDay()->endOfDay();

        // Toutes les requêtes analytics passent par le replica dédié
        $orders = Order::on('mysql_analytics')->whereBetween('created_at', [$yesterday, $endOfYesterday]);

        $stats = [
            'date' => $yesterday->toDateString(),
            'total_orders' => $orders->count(),
            'delivered_orders' => $orders->where('status', OrderStatus::DELIVERED)->count(),
            'cancelled_orders' => $orders->where('status', OrderStatus::CANCELLED)->count(),
            'total_revenue' => $orders->where('status', OrderStatus::DELIVERED)->sum('total_price'),
            'new_clients' => User::on('mysql_analytics')->clients()->whereBetween('created_at', [$yesterday, $endOfYesterday])->count(),
            'new_couriers' => User::on('mysql_analytics')->couriers()->whereBetween('created_at', [$yesterday, $endOfYesterday])->count(),
            'active_couriers' => User::on('mysql_analytics')->couriers()->active()->available()->count(),
        ];

        Log::channel('api')->info('Daily Report', $stats);

        \Sentry\configureScope(function (\Sentry\State\Scope $scope) use ($stats): void {
            $scope->setContext('daily_report', $stats);
            \Sentry\captureMessage(
                "[DAILY] {$stats['date']} — {$stats['total_orders']} commandes, {$stats['total_revenue']} FCFA",
                \Sentry\Severity::info()
            );
        });
    }
}
