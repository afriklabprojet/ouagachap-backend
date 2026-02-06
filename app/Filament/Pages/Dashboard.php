<?php

namespace App\Filament\Pages;

use App\Filament\Widgets;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    
    protected static ?string $navigationLabel = 'Tableau de bord';
    
    protected static ?string $title = 'Tableau de bord OUAGA CHAP';
    
    protected static ?int $navigationSort = -2;

    public function getWidgets(): array
    {
        return [
            Widgets\StatsOverview::class,
            Widgets\RevenueChart::class,
            Widgets\OrdersChart::class,
            Widgets\CouriersPerformance::class,
            Widgets\LatestOrders::class,
        ];
    }

    public function getColumns(): int|string|array
    {
        return [
            'default' => 1,
            'md' => 2,
            'lg' => 2,
        ];
    }
}
