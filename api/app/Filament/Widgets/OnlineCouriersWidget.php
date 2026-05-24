<?php

namespace App\Filament\Widgets;

use App\Enums\UserRole;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OnlineCouriersWidget extends BaseWidget
{
    protected static ?int $sort = 6;

    protected int | string | array $columnSpan = 'full';

    protected int | string | array $columns = [
        'default' => 2,
        'md'      => 3,
    ];

    // Rafraîchir toutes les 10 secondes
    protected static ?string $pollingInterval = '10s';

    protected function getStats(): array
    {
        // Coursiers disponibles (en ligne)
        $availableCouriers = User::where('role', UserRole::COURIER)
            ->where('is_available', true)
            ->count();

        // Coursiers occupés (non disponibles mais actifs)
        $busyCouriers = User::where('role', UserRole::COURIER)
            ->where('is_available', false)
            ->where('status', 'active')
            ->count();

        // Total coursiers
        $totalCouriers = User::where('role', UserRole::COURIER)->count();

        // Coursiers par type de véhicule (disponibles)
        $motorcycles = User::where('role', UserRole::COURIER)
            ->where('is_available', true)
            ->where('vehicle_type', 'moto')
            ->count();

        $bicycles = User::where('role', UserRole::COURIER)
            ->where('is_available', true)
            ->where('vehicle_type', 'velo')
            ->count();

        $cars = User::where('role', UserRole::COURIER)
            ->where('is_available', true)
            ->where('vehicle_type', 'voiture')
            ->count();

        return [
            Stat::make('🟢 Coursiers disponibles', $availableCouriers)
                ->description("{$availableCouriers} sur {$totalCouriers} coursiers")
                ->descriptionIcon('heroicon-m-signal')
                ->color('success')
                ->chart($this->getOnlineHistory()),

            Stat::make('✅ Prêts', $availableCouriers)
                ->description('Prêts pour une livraison')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('🚚 En course', $busyCouriers)
                ->description('Livraison en cours')
                ->descriptionIcon('heroicon-m-truck')
                ->color('warning'),

            Stat::make('🏍️ Motos', $motorcycles)
                ->description('Disponibles')
                ->descriptionIcon('heroicon-m-bolt')
                ->color('primary'),

            Stat::make('🚲 Vélos', $bicycles)
                ->description('Disponibles')
                ->descriptionIcon('heroicon-m-heart')
                ->color('info'),

            Stat::make('🚗 Voitures', $cars)
                ->description('Disponibles')
                ->descriptionIcon('heroicon-m-truck')
                ->color('gray'),
        ];
    }

    /**
     * Retourne un historique simulé pour le graphique
     */
    protected function getOnlineHistory(): array
    {
        $history = [];
        $currentCount = User::where('role', UserRole::COURIER)
            ->where('is_available', true)
            ->count();

        for ($i = 0; $i < 7; $i++) {
            $variation = rand(-2, 2);
            $history[] = max(0, $currentCount + $variation);
        }

        return $history;
    }
}
