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
    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';
    
    protected static ?string $pollingInterval = '15s';

    protected function getHeading(): ?string
    {
        $user = auth()->user();
        $hour = now()->hour;
        
        $greeting = match(true) {
            $hour < 12 => '🌅 Bonjour',
            $hour < 18 => '☀️ Bon après-midi',
            default => '🌙 Bonsoir',
        };

        return $greeting . ', ' . explode(' ', $user->name)[0] . ' ! — ' . now()->translatedFormat('l j F Y');
    }

    protected function getDescription(): ?string
    {
        return 'Voici un aperçu de votre activité en temps réel.';
    }

    protected function getStats(): array
    {
        // Données d'aujourd'hui
        $todayOrders = Order::whereDate('created_at', today())->count();
        $todayDelivered = Order::whereDate('delivered_at', today())
            ->where('status', OrderStatus::DELIVERED)
            ->count();
        $todayRevenue = Order::whereDate('delivered_at', today())
            ->where('status', OrderStatus::DELIVERED)
            ->sum('platform_fee');
        
        // Données d'hier (pour comparaison)
        $yesterdayOrders = Order::whereDate('created_at', today()->subDay())->count();
        $yesterdayRevenue = Order::whereDate('delivered_at', today()->subDay())
            ->where('status', OrderStatus::DELIVERED)
            ->sum('platform_fee');
        
        // Tendances
        $ordersChange = $yesterdayOrders > 0 
            ? round((($todayOrders - $yesterdayOrders) / $yesterdayOrders) * 100, 1)
            : ($todayOrders > 0 ? 100 : 0);
        $revenueChange = $yesterdayRevenue > 0 
            ? round((($todayRevenue - $yesterdayRevenue) / $yesterdayRevenue) * 100, 1)
            : ($todayRevenue > 0 ? 100 : 0);
        
        // Coursiers
        $activeCouriers = User::where('role', UserRole::COURIER)
            ->where('is_available', true)
            ->count();
        $pendingCouriers = User::where('role', UserRole::COURIER)
            ->where('status', 'pending')
            ->count();
        $totalCouriers = User::where('role', UserRole::COURIER)->count();
        
        // Commandes
        $pendingOrders = Order::where('status', OrderStatus::PENDING)->count();
        $inProgressOrders = Order::whereIn('status', [
            OrderStatus::ASSIGNED,
            OrderStatus::PICKED_UP,
        ])->count();
        
        // Retraits en attente
        $pendingWithdrawals = Withdrawal::where('status', 'pending')->count();
        $pendingWithdrawalsAmount = Withdrawal::where('status', 'pending')->sum('amount');

        // Clients
        $todayNewClients = User::where('role', UserRole::CLIENT)
            ->whereDate('created_at', today())
            ->count();
        $totalClients = User::where('role', UserRole::CLIENT)->count();
        
        // Graphiques tendance (7 derniers jours)
        $weeklyOrders = $this->getWeeklyData(Order::class);
        $weeklyRevenue = $this->getWeeklyRevenue();
        
        return [
            Stat::make('Commandes aujourd\'hui', $todayOrders)
                ->description($todayDelivered . ' livrées • ' . ($ordersChange >= 0 ? '+' : '') . $ordersChange . '% vs hier')
                ->descriptionIcon($ordersChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->chart($weeklyOrders)
                ->color($ordersChange >= 0 ? 'success' : 'danger')
                ->extraAttributes([
                    'class' => 'cursor-pointer hover:shadow-lg transition-shadow',
                ]),
                
            Stat::make('Revenus du jour', number_format($todayRevenue, 0, ',', ' ') . ' F')
                ->description(($revenueChange >= 0 ? '+' : '') . $revenueChange . '% vs hier')
                ->descriptionIcon($revenueChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->chart($weeklyRevenue)
                ->color($revenueChange >= 0 ? 'success' : 'warning')
                ->extraAttributes([
                    'class' => 'cursor-pointer hover:shadow-lg transition-shadow',
                ]),
                
            Stat::make('Coursiers en ligne', $activeCouriers . ' / ' . $totalCouriers)
                ->description($pendingCouriers > 0 ? $pendingCouriers . ' en attente de validation' : 'Tous validés')
                ->descriptionIcon($pendingCouriers > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($activeCouriers < 3 ? 'danger' : ($activeCouriers < 5 ? 'warning' : 'success'))
                ->extraAttributes([
                    'class' => 'cursor-pointer hover:shadow-lg transition-shadow',
                ])
                ->url(route('filament.admin.pages.couriers-tracking')),
                
            Stat::make('Commandes en attente', $pendingOrders)
                ->description($inProgressOrders . ' en cours de livraison')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pendingOrders > 10 ? 'danger' : ($pendingOrders > 5 ? 'warning' : 'info'))
                ->extraAttributes([
                    'class' => 'cursor-pointer hover:shadow-lg transition-shadow',
                ])
                ->url(route('filament.admin.resources.orders.index')),

            Stat::make('Retraits en attente', $pendingWithdrawals)
                ->description(number_format($pendingWithdrawalsAmount, 0, ',', ' ') . ' FCFA à traiter')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color($pendingWithdrawals > 5 ? 'danger' : ($pendingWithdrawals > 0 ? 'warning' : 'success'))
                ->url(route('filament.admin.resources.withdrawals.index'))
                ->extraAttributes([
                    'class' => 'cursor-pointer hover:shadow-lg transition-shadow',
                ]),

            Stat::make('Nouveaux clients', '+' . $todayNewClients . ' aujourd\'hui')
                ->description('Total: ' . number_format($totalClients, 0, ',', ' ') . ' clients')
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('info')
                ->extraAttributes([
                    'class' => 'cursor-pointer hover:shadow-lg transition-shadow',
                ])
                ->url(route('filament.admin.resources.users.index')),
        ];
    }

    protected function getWeeklyData(string $model): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $data[] = $model::whereDate('created_at', $date)->count();
        }
        return $data;
    }

    protected function getWeeklyRevenue(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $data[] = (int) Order::whereDate('delivered_at', $date)
                ->where('status', OrderStatus::DELIVERED)
                ->sum('platform_fee') / 100;
        }
        return $data;
    }
}
