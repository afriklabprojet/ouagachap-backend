<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CourierPerformanceWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $today = today();
        $yesterday = today()->subDay();

        // Taux d'acceptation (commandes assignées / commandes créées aujourd'hui)
        $todayCreated = Order::whereDate('created_at', $today)->count();
        $todayAssigned = Order::whereDate('created_at', $today)
            ->whereNotNull('courier_id')
            ->count();
        $acceptanceRate = $todayCreated > 0 ? round(($todayAssigned / $todayCreated) * 100, 1) : 0;

        $yesterdayCreated = Order::whereDate('created_at', $yesterday)->count();
        $yesterdayAssigned = Order::whereDate('created_at', $yesterday)
            ->whereNotNull('courier_id')
            ->count();
        $yesterdayRate = $yesterdayCreated > 0 ? round(($yesterdayAssigned / $yesterdayCreated) * 100, 1) : 0;
        $rateEvolution = $acceptanceRate - $yesterdayRate;

        // Délai moyen de livraison (created_at → delivered_at) aujourd'hui en minutes
        $avgDeliveryMinutes = Order::where('status', OrderStatus::DELIVERED)
            ->whereDate('delivered_at', $today)
            ->whereNotNull('delivered_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, delivered_at)) as avg_minutes')
            ->value('avg_minutes');
        $avgDeliveryMinutes = $avgDeliveryMinutes ? (int) round($avgDeliveryMinutes) : null;

        $yesterdayAvg = Order::where('status', OrderStatus::DELIVERED)
            ->whereDate('delivered_at', $yesterday)
            ->whereNotNull('delivered_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, delivered_at)) as avg_minutes')
            ->value('avg_minutes');
        $yesterdayAvg = $yesterdayAvg ? (int) round($yesterdayAvg) : null;

        $deliveryDelayEvolution = ($avgDeliveryMinutes && $yesterdayAvg)
            ? $avgDeliveryMinutes - $yesterdayAvg
            : null;

        // Coursiers avec + de 3 livraisons aujourd'hui (top performers)
        $topCouriersCount = DB::table('orders')
            ->selectRaw('courier_id')
            ->whereDate('delivered_at', $today)
            ->where('status', OrderStatus::DELIVERED->value)
            ->whereNotNull('courier_id')
            ->groupBy('courier_id')
            ->havingRaw('COUNT(*) >= 3')
            ->get()->count();

        // Note moyenne des livraisons du jour
        $avgRatingToday = DB::table('ratings')
            ->whereDate('created_at', $today)
            ->avg('rating');
        $avgRatingToday = $avgRatingToday ? round($avgRatingToday, 1) : null;

        // Taux d'annulation du jour
        $cancelledToday = Order::whereDate('created_at', $today)
            ->where('status', OrderStatus::CANCELLED)
            ->count();
        $cancellationRate = $todayCreated > 0 ? round(($cancelledToday / $todayCreated) * 100, 1) : 0;

        // Historique taux d'acceptation 7 jours
        $acceptanceHistory = $this->getAcceptanceHistory();

        return [
            Stat::make('Taux d\'acceptation', $acceptanceRate . '%')
                ->description($this->formatEvolution($rateEvolution, '%', false) . ' vs hier')
                ->descriptionIcon($rateEvolution >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->chart($acceptanceHistory)
                ->color($this->acceptanceColor($acceptanceRate)),

            Stat::make(
                'Délai moyen de livraison',
                $avgDeliveryMinutes !== null ? $avgDeliveryMinutes . ' min' : '—'
            )
                ->description(
                    $deliveryDelayEvolution !== null
                        ? $this->formatEvolution($deliveryDelayEvolution, ' min', true) . ' vs hier'
                        : 'Pas encore de livraisons'
                )
                ->descriptionIcon($deliveryDelayEvolution !== null && $deliveryDelayEvolution <= 0
                    ? 'heroicon-m-arrow-trending-down'
                    : 'heroicon-m-clock')
                ->color($this->deliveryTimeColor($avgDeliveryMinutes)),

            Stat::make('Taux d\'annulation', $cancellationRate . '%')
                ->description($cancelledToday . ' commande(s) annulée(s) aujourd\'hui')
                ->descriptionIcon($cancellationRate > 15 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($cancellationRate > 15 ? 'danger' : ($cancellationRate > 8 ? 'warning' : 'success')),

            Stat::make('Coursiers actifs (3+ livraisons)', $topCouriersCount)
                ->description('Ont complété ≥ 3 livraisons aujourd\'hui')
                ->descriptionIcon('heroicon-m-star')
                ->color('primary'),

            Stat::make(
                'Note moyenne du jour',
                $avgRatingToday !== null ? $avgRatingToday . ' / 5' : '—'
            )
                ->description('Basée sur les évaluations reçues aujourd\'hui')
                ->descriptionIcon('heroicon-m-star')
                ->color($this->ratingColor($avgRatingToday)),
        ];
    }

    private function formatEvolution(float $delta, string $unit, bool $invertPositive): string
    {
        $sign = $delta > 0 ? '+' : '';
        $positive = $invertPositive ? $delta <= 0 : $delta >= 0;
        return $sign . $delta . $unit;
    }

    private function acceptanceColor(float $rate): string
    {
        if ($rate >= 80) return 'success';
        if ($rate >= 60) return 'warning';
        return 'danger';
    }

    private function deliveryTimeColor(?int $minutes): string
    {
        if ($minutes === null) return 'gray';
        if ($minutes <= 30) return 'success';
        if ($minutes <= 50) return 'warning';
        return 'danger';
    }

    private function ratingColor(?float $rating): string
    {
        if ($rating === null) return 'gray';
        if ($rating >= 4.5) return 'success';
        if ($rating >= 3.5) return 'warning';
        return 'danger';
    }

    private function getAcceptanceHistory(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $created = Order::whereDate('created_at', $date)->count();
            $assigned = Order::whereDate('created_at', $date)->whereNotNull('courier_id')->count();
            $data[] = $created > 0 ? (int) round(($assigned / $created) * 100) : 0;
        }
        return $data;
    }
}
