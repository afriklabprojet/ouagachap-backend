<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GenerateDailyReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public ?string $date = null
    ) {
        $this->date = $date ?? now()->subDay()->format('Y-m-d');
    }

    public function handle(): void
    {
        $date = $this->date;

        // Statistiques des commandes
        $ordersStats = Order::whereDate('created_at', $date)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = "delivered" THEN 1 ELSE 0 END) as delivered,
                SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) as cancelled,
                SUM(total_price) as revenue,
                SUM(courier_earnings) as fees
            ')
            ->first();

        // Nouveaux utilisateurs
        $newClients = User::where('role', 'client')
            ->whereDate('created_at', $date)
            ->count();

        $newCouriers = User::where('role', 'courier')
            ->whereDate('created_at', $date)
            ->count();

        // Coursiers actifs
        $activeCouriers = User::where('role', 'courier')
            ->whereHas('courierOrders', fn ($q) => $q->whereDate('delivered_at', $date))
            ->count();

        $report = [
            'date' => $date,
            'generated_at' => now()->toISOString(),
            'orders' => [
                'total' => $ordersStats->total ?? 0,
                'delivered' => $ordersStats->delivered ?? 0,
                'cancelled' => $ordersStats->cancelled ?? 0,
                'delivery_rate' => $ordersStats->total > 0 
                    ? round(($ordersStats->delivered / $ordersStats->total) * 100, 2) 
                    : 0,
            ],
            'revenue' => [
                'total' => $ordersStats->revenue ?? 0,
                'delivery_fees' => $ordersStats->fees ?? 0,
            ],
            'users' => [
                'new_clients' => $newClients,
                'new_couriers' => $newCouriers,
                'active_couriers' => $activeCouriers,
            ],
        ];

        // Sauvegarder le rapport
        $filename = "reports/daily/{$date}.json";
        Storage::put($filename, json_encode($report, JSON_PRETTY_PRINT));

        Log::info("Rapport quotidien généré: {$filename}");
    }
}
