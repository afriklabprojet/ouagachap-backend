<?php

namespace App\Filament\Resources\ComplaintResource\Widgets;

use App\Models\Complaint;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ComplaintStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total', Complaint::count())
                ->description('Toutes les réclamations')
                ->icon('heroicon-o-document-text')
                ->color('gray'),
                
            Stat::make('Ouvertes', Complaint::where('status', 'open')->count())
                ->description('À traiter')
                ->icon('heroicon-o-exclamation-circle')
                ->color('danger'),
                
            Stat::make('En cours', Complaint::where('status', 'in_progress')->count())
                ->description('En traitement')
                ->icon('heroicon-o-clock')
                ->color('warning'),
                
            Stat::make('Résolues', Complaint::where('status', 'resolved')->count())
                ->description('Cette semaine')
                ->icon('heroicon-o-check-circle')
                ->color('success'),
        ];
    }
}
