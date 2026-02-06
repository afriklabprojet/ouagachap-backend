<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OnlineCouriersWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    
    protected int | string | array $columnSpan = 'full';
    
    // Rafraîchir toutes les 10 secondes
    protected static ?string $pollingInterval = '10s';

    protected function getStats(): array
    {
        // Coursiers en ligne
        $onlineCouriers = User::where('role', 'coursier')
            ->where('is_online', true)
            ->count();
            
        // Coursiers disponibles (en ligne et pas en livraison)
        $availableCouriers = User::where('role', 'coursier')
            ->where('is_online', true)
            ->where('is_available', true)
            ->count();
            
        // Coursiers en livraison
        $busyCouriers = User::where('role', 'coursier')
            ->where('is_online', true)
            ->where('is_available', false)
            ->count();
            
        // Total coursiers
        $totalCouriers = User::where('role', 'coursier')->count();
        
        // Coursiers par type de véhicule en ligne
        $motorcycles = User::where('role', 'coursier')
            ->where('is_online', true)
            ->where('vehicle_type', 'moto')
            ->count();
            
        $bicycles = User::where('role', 'coursier')
            ->where('is_online', true)
            ->where('vehicle_type', 'velo')
            ->count();
            
        $cars = User::where('role', 'coursier')
            ->where('is_online', true)
            ->where('vehicle_type', 'voiture')
            ->count();

        return [
            Stat::make('🟢 Coursiers en ligne', $onlineCouriers)
                ->description("{$onlineCouriers} sur {$totalCouriers} coursiers")
                ->descriptionIcon('heroicon-m-signal')
                ->color('success')
                ->chart($this->getOnlineHistory()),
                
            Stat::make('✅ Disponibles', $availableCouriers)
                ->description('Prêts pour une livraison')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
                
            Stat::make('🚚 En course', $busyCouriers)
                ->description('Livraison en cours')
                ->descriptionIcon('heroicon-m-truck')
                ->color('warning'),
                
            Stat::make('🏍️ Motos', $motorcycles)
                ->description('En ligne')
                ->descriptionIcon('heroicon-m-bolt')
                ->color('primary'),
                
            Stat::make('🚲 Vélos', $bicycles)
                ->description('En ligne')
                ->descriptionIcon('heroicon-m-heart')
                ->color('info'),
                
            Stat::make('🚗 Voitures', $cars)
                ->description('En ligne')
                ->descriptionIcon('heroicon-m-truck')
                ->color('gray'),
        ];
    }
    
    /**
     * Retourne un historique simulé pour le graphique
     * Dans une vraie application, vous stockeriez ces données
     */
    protected function getOnlineHistory(): array
    {
        // Simuler un historique des 7 dernières heures
        $history = [];
        $currentCount = User::where('role', 'coursier')
            ->where('is_online', true)
            ->count();
            
        for ($i = 0; $i < 7; $i++) {
            // Variation aléatoire pour simulation
            $variation = rand(-2, 2);
            $history[] = max(0, $currentCount + $variation);
        }
        
        return $history;
    }
}
