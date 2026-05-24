<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Models\User;
use Filament\Pages\Page;
use Livewire\Attributes\Computed;

class CouriersTracking extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    protected static ?string $navigationLabel = 'Suivi GPS';

    protected static ?string $title = 'Suivi GPS des Coursiers';

    protected static ?string $slug = 'couriers-tracking';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationGroup = 'Suivi en direct';

    protected static string $view = 'filament.pages.couriers-tracking';

    // Rafraîchir toutes les 5 secondes
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

    public static function getNavigationBadge(): ?string
    {
        $online = User::where('role', UserRole::COURIER)->where('is_available', true)->count();
        return $online > 0 ? (string) $online : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'success';
    }
}
