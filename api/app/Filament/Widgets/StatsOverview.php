<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\User;
use App\Models\Withdrawal;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $pollingInterval = '15s';

    private const CARD_CLASS = 'cursor-pointer hover:shadow-lg transition-shadow';

    // -----------------------------------------------------------------------
    // Helpers — extracted to reduce cognitive complexity of getStats()
    // -----------------------------------------------------------------------

    private function calcPercentChange(float $previous, float $current): float
    {
        if ($previous > 0) {
            return round((($current - $previous) / $previous) * 100, 1);
        }

        return $current > 0 ? 100.0 : 0.0;
    }

    private function courierColor(int $active): string
    {
        if ($active < 3) {
            return 'danger';
        }
        if ($active < 5) {
            return 'warning';
        }

        return 'success';
    }

    private function pendingOrdersColor(int $pending): string
    {
        if ($pending > 10) {
            return 'danger';
        }
        if ($pending > 5) {
            return 'warning';
        }

        return 'info';
    }

    private function withdrawalColor(int $pending): string
    {
        if ($pending > 5) {
            return 'danger';
        }
        if ($pending > 0) {
            return 'warning';
        }

        return 'success';
    }

    // -----------------------------------------------------------------------

    protected function getStats(): array
    {
        // Données d'aujourd'hui
        $todayOrders = Order::whereDate('created_at', today())->count();
        $todayDelivered = Order::whereDate('delivered_at', today())
            ->where('status', OrderStatus::DELIVERED)
            ->count();
        $todayRevenue = Order::whereDate('delivered_at', today())
            ->where('status', OrderStatus::DELIVERED)
            ->sum('commission_amount');

        // Données d'hier (pour comparaison)
        $yesterdayOrders = Order::whereDate('created_at', today()->subDay())->count();
        $yesterdayRevenue = Order::whereDate('delivered_at', today()->subDay())
            ->where('status', OrderStatus::DELIVERED)
            ->sum('commission_amount');

        // Tendances
        $ordersChange  = $this->calcPercentChange((float) $yesterdayOrders, (float) $todayOrders);
        $revenueChange = $this->calcPercentChange((float) $yesterdayRevenue, (float) $todayRevenue);

        // Coursiers
        $activeCouriers = User::where('role', UserRole::COURIER)
            ->where('is_available', true)
            ->count();
        $pendingCouriers = User::where('role', UserRole::COURIER)
            ->where('status', 'pending')
            ->count();
        $totalCouriers = User::where('role', UserRole::COURIER)->count();

        // Commandes
        $pendingOrders   = Order::where('status', OrderStatus::PENDING)->count();
        $inProgressOrders = Order::whereIn('status', OrderStatus::activeStatuses())->count();

        // Retraits en attente
        $pendingWithdrawals       = Withdrawal::where('status', 'pending')->count();
        $pendingWithdrawalsAmount = Withdrawal::where('status', 'pending')->sum('amount');

        // Clients
        $todayNewClients = User::where('role', UserRole::CLIENT)
            ->whereDate('created_at', today())
            ->count();
        $totalClients = User::where('role', UserRole::CLIENT)->count();

        // Graphiques tendance (7 derniers jours)
        $weeklyOrders  = $this->getWeeklyData(Order::class);
        $weeklyRevenue = $this->getWeeklyRevenue();

        return [
            Stat::make('Commandes aujourd\'hui', $todayOrders)
                ->description($todayDelivered . ' livrées • ' . ($ordersChange >= 0 ? '+' : '') . $ordersChange . '% vs hier')
                ->descriptionIcon($ordersChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->chart($weeklyOrders)
                ->color($ordersChange >= 0 ? 'success' : 'danger')
                ->extraAttributes(['class' => self::CARD_CLASS]),

            Stat::make('Revenus du jour', number_format($todayRevenue, 0, ',', ' ') . ' F')
                ->description(($revenueChange >= 0 ? '+' : '') . $revenueChange . '% vs hier')
                ->descriptionIcon($revenueChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->chart($weeklyRevenue)
                ->color($revenueChange >= 0 ? 'success' : 'warning')
                ->extraAttributes(['class' => self::CARD_CLASS]),

            Stat::make('Coursiers en ligne', $activeCouriers . ' / ' . $totalCouriers)
                ->description($pendingCouriers > 0 ? $pendingCouriers . ' en attente de validation' : 'Tous validés')
                ->descriptionIcon($pendingCouriers > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($this->courierColor($activeCouriers))
                ->extraAttributes(['class' => self::CARD_CLASS]),

            Stat::make('Commandes en attente', $pendingOrders)
                ->description($inProgressOrders . ' en cours de livraison')
                ->descriptionIcon('heroicon-m-clock')
                ->color($this->pendingOrdersColor($pendingOrders))
                ->extraAttributes(['class' => self::CARD_CLASS])
                ->url(route('filament.admin.resources.orders.index')),

            Stat::make('Retraits en attente', $pendingWithdrawals)
                ->description(number_format($pendingWithdrawalsAmount, 0, ',', ' ') . ' FCFA à traiter')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color($this->withdrawalColor($pendingWithdrawals))
                ->url(route('filament.admin.resources.withdrawals.index'))
                ->extraAttributes(['class' => self::CARD_CLASS]),

            Stat::make('Nouveaux clients', '+' . $todayNewClients . ' aujourd\'hui')
                ->description('Total: ' . number_format($totalClients, 0, ',', ' ') . ' clients')
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('info')
                ->extraAttributes(['class' => self::CARD_CLASS])
                ->url(route('filament.admin.resources.users.index')),
        ];
    }

    protected function getWeeklyData(string $model): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date   = Carbon::now()->subDays($i);
            $data[] = $model::whereDate('created_at', $date)->count();
        }

        return $data;
    }

    protected function getWeeklyRevenue(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date   = Carbon::now()->subDays($i);
            $data[] = (int) Order::whereDate('delivered_at', $date)
                ->where('status', OrderStatus::DELIVERED)
                ->sum('commission_amount') / 100;
        }

        return $data;
    }
}
