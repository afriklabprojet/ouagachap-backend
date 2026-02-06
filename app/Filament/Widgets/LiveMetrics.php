<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Models\Withdrawal;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;

class LiveMetrics extends Widget
{
    protected static string $view = 'filament.widgets.live-metrics';

    protected static ?int $sort = -5;

    protected int|string|array $columnSpan = 'full';

    // Rafraîchissement toutes les 10 secondes
    protected static ?string $pollingInterval = '10s';

    public function getViewData(): array
    {
        // Statistiques en temps réel
        $todayOrders = Order::whereDate('created_at', today())->count();
        $todayDelivered = Order::where('status', OrderStatus::DELIVERED)
            ->whereDate('delivered_at', today())
            ->count();
        $todayRevenue = Order::where('status', OrderStatus::DELIVERED)
            ->whereDate('delivered_at', today())
            ->sum('platform_fee');
        
        // Comparaison avec hier
        $yesterdayOrders = Order::whereDate('created_at', today()->subDay())->count();
        $yesterdayRevenue = Order::where('status', OrderStatus::DELIVERED)
            ->whereDate('delivered_at', today()->subDay())
            ->sum('platform_fee');

        // Tendances
        $ordersTrend = $this->calculateTrend($todayOrders, $yesterdayOrders);
        $revenueTrend = $this->calculateTrend($todayRevenue, $yesterdayRevenue);

        // Coursiers
        $onlineCouriers = User::where('role', UserRole::COURIER)
            ->where('is_available', true)
            ->count();
        $totalCouriers = User::where('role', UserRole::COURIER)->count();

        // Commandes par statut
        $pendingOrders = Order::where('status', OrderStatus::PENDING)->count();
        $inProgressOrders = Order::whereIn('status', [
            OrderStatus::ASSIGNED,
            OrderStatus::PICKED_UP,
        ])->count();

        // Statistiques de la semaine
        $weeklyStats = $this->getWeeklyStats();

        // Taux de livraison
        $totalTodayOrders = Order::whereDate('created_at', today())->count();
        $deliveryRate = $totalTodayOrders > 0 
            ? round(($todayDelivered / $totalTodayOrders) * 100, 1) 
            : 0;

        return [
            'todayOrders' => $todayOrders,
            'todayDelivered' => $todayDelivered,
            'todayRevenue' => $todayRevenue,
            'ordersTrend' => $ordersTrend,
            'revenueTrend' => $revenueTrend,
            'onlineCouriers' => $onlineCouriers,
            'totalCouriers' => $totalCouriers,
            'pendingOrders' => $pendingOrders,
            'inProgressOrders' => $inProgressOrders,
            'weeklyStats' => $weeklyStats,
            'deliveryRate' => $deliveryRate,
        ];
    }

    protected function calculateTrend($current, $previous): array
    {
        if ($previous == 0) {
            return [
                'value' => $current > 0 ? 100 : 0,
                'direction' => $current > 0 ? 'up' : 'neutral',
            ];
        }

        $change = round((($current - $previous) / $previous) * 100, 1);
        
        return [
            'value' => abs($change),
            'direction' => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'neutral'),
        ];
    }

    protected function getWeeklyStats(): array
    {
        $data = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $data[] = [
                'day' => $date->translatedFormat('D'),
                'orders' => Order::whereDate('created_at', $date)->count(),
                'revenue' => Order::where('status', OrderStatus::DELIVERED)
                    ->whereDate('delivered_at', $date)
                    ->sum('platform_fee'),
            ];
        }

        return $data;
    }
}
