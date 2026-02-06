<?php

namespace App\Filament\Resources\TrafficIncidentResource\Widgets;

use App\Models\TrafficIncident;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TrafficStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $activeIncidents = TrafficIncident::active()->count();
        $todayIncidents = TrafficIncident::whereDate('created_at', today())->count();
        $severeIncidents = TrafficIncident::active()->whereIn('severity', ['severe', 'high'])->count();
        $resolvedToday = TrafficIncident::whereDate('resolved_at', today())->count();

        return [
            Stat::make('Incidents actifs', $activeIncidents)
                ->description('En cours actuellement')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($activeIncidents >= 10 ? 'danger' : ($activeIncidents >= 5 ? 'warning' : 'success'))
                ->chart([7, 3, 4, 5, $activeIncidents]),

            Stat::make('Aujourd\'hui', $todayIncidents)
                ->description('Signalés aujourd\'hui')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('info'),

            Stat::make('Critiques', $severeIncidents)
                ->description('Sévères ou importants')
                ->descriptionIcon('heroicon-m-fire')
                ->color($severeIncidents > 0 ? 'danger' : 'success'),

            Stat::make('Résolus', $resolvedToday)
                ->description('Résolus aujourd\'hui')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
        ];
    }
}
