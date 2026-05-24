<?php

namespace App\Filament\Widgets;

use App\Enums\UserRole;
use App\Models\User;
use Filament\Widgets\Widget;
use Livewire\Attributes\Computed;

class CouriersMap extends Widget
{
    protected static string $view = 'filament.widgets.couriers-map';

    protected static ?string $heading = 'Carte des Coursiers';

    protected static ?int $sort = 8;

    protected int|string|array $columnSpan = 'full';

    // Rafraîchir toutes les 5 secondes pour le suivi en direct
    protected static ?string $pollingInterval = '5s';

    #[Computed]
    public function getCouriers(): array
    {
        return User::where('role', UserRole::COURIER)
            ->where('is_available', true)
            ->whereNotNull('current_latitude')
            ->whereNotNull('current_longitude')
            ->get()
            ->map(fn ($courier) => [
                'id' => $courier->id,
                'name' => $courier->name,
                'phone' => $courier->phone ?? '',
                'vehicle' => $courier->vehicle_type ?? 'moto',
                'lat' => (float) $courier->current_latitude,
                'lng' => (float) $courier->current_longitude,
                'updated' => $courier->location_updated_at?->diffForHumans() ?? 'Maintenant',
                'rating' => number_format($courier->average_rating ?? 0, 1),
                'deliveries' => $courier->total_orders ?? 0,
            ])
            ->toArray();
    }

    #[Computed]
    public function getOnlineCouriersCount(): int
    {
        return User::where('role', UserRole::COURIER)->where('is_available', true)->count();
    }

    #[Computed]
    public function getTotalCouriersCount(): int
    {
        return User::where('role', UserRole::COURIER)->count();
    }

    #[Computed]
    public function getOfflineCouriersCount(): int
    {
        return $this->getTotalCouriersCount() - $this->getOnlineCouriersCount();
    }
}
