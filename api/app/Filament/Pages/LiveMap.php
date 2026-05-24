<?php

namespace App\Filament\Pages;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\User;
use Filament\Pages\Page;

class LiveMap extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-map-pin';
    protected static string  $view            = 'filament.pages.live-map';
    protected static ?string $navigationLabel = 'Carte Live';
    protected static ?string $title           = 'Carte Live — Supervision Temps Réel';
    protected static ?string $navigationGroup = 'Supervision';
    protected static ?int    $navigationSort  = 1;

    /** @var array<int, array<string, mixed>> */
    public array $couriers = [];

    /** @var array<int, array<string, mixed>> */
    public array $orders = [];

    /** @var array<string, int> */
    public array $stats = [
        'total_couriers'     => 0,
        'available_couriers' => 0,
        'busy_couriers'      => 0,
        'active_orders'      => 0,
    ];

    /** @var array<int, array<float>> Points [lat, lng, intensity] pour heatmap */
    public array $heatmap = [];

    public function mount(): void
    {
        $this->loadData();
    }

    /** Méthode appelée par wire:poll.5000ms */
    public function loadData(): void
    {
        $this->couriers = $this->fetchCouriers();
        $this->orders   = $this->fetchOrders();
        $this->stats    = $this->fetchStats();
        $this->heatmap  = $this->fetchHeatmap();
    }

    // -------------------------------------------------------------------------
    // Queries internes
    // -------------------------------------------------------------------------

    private function fetchCouriers(): array
    {
        return User::query()
            ->where('role', UserRole::COURIER)
            ->whereNotNull('current_latitude')
            ->whereNotNull('current_longitude')
            ->where('location_updated_at', '>=', now()->subMinutes(15))
            ->get([
                'id', 'name', 'phone',
                'current_latitude', 'current_longitude',
                'is_available',
                'location_updated_at', 'vehicle_type',
            ])
            ->map(fn (User $u) => [
                'id'           => $u->id,
                'name'         => $u->name,
                'phone'        => $u->phone ?? '',
                'lat'          => (float) $u->current_latitude,
                'lng'          => (float) $u->current_longitude,
                'battery'      => $u->battery_level ?? 100,
                'available'    => (bool) $u->is_available,
                'vehicle_type' => $u->vehicle_type ?? 'moto',
                'freshness'    => $u->location_updated_at
                    ? now()->diffInSeconds($u->location_updated_at)
                    : 999,
            ])
            ->toArray();
    }

    private function fetchOrders(): array
    {
        $activeStatuses = [
            OrderStatus::ASSIGNED,
            OrderStatus::ACCEPTED,
            OrderStatus::PICKING_UP,
            OrderStatus::PICKED_UP,
            OrderStatus::IN_TRANSIT,
        ];

        return Order::query()
            ->whereIn('status', $activeStatuses)
            ->whereNotNull('pickup_latitude')
            ->whereNotNull('dropoff_latitude')
            ->with('courier:id,name,current_latitude,current_longitude')
            ->get([
                'id', 'status', 'courier_id',
                'pickup_latitude', 'pickup_longitude',
                'dropoff_latitude', 'dropoff_longitude',
            ])
            ->map(fn (Order $o) => [
                'id'     => $o->id,
                'status' => $o->status->value,
                'pickup'  => [
                    'lat' => (float) $o->pickup_latitude,
                    'lng' => (float) $o->pickup_longitude,
                ],
                'dropoff' => [
                    'lat' => (float) $o->dropoff_latitude,
                    'lng' => (float) $o->dropoff_longitude,
                ],
                'courier' => $o->courier ? [
                    'id'   => $o->courier->id,
                    'name' => $o->courier->name,
                    'lat'  => (float) $o->courier->current_latitude,
                    'lng'  => (float) $o->courier->current_longitude,
                ] : null,
            ])
            ->toArray();
    }

    private function fetchStats(): array
    {
        $activeStatuses = [
            OrderStatus::ASSIGNED,
            OrderStatus::ACCEPTED,
            OrderStatus::PICKING_UP,
            OrderStatus::PICKED_UP,
            OrderStatus::IN_TRANSIT,
        ];

        $busyCouriers = Order::query()
            ->whereIn('status', $activeStatuses)
            ->whereNotNull('courier_id')
            ->distinct('courier_id')
            ->count('courier_id');

        return [
            'total_couriers'     => User::where('role', UserRole::COURIER)->count(),
            'available_couriers' => User::where('role', UserRole::COURIER)
                ->where('is_available', true)
                ->where('location_updated_at', '>=', now()->subMinutes(10))
                ->count(),
            'busy_couriers'      => $busyCouriers,
            'active_orders'      => Order::whereIn('status', $activeStatuses)->count(),
        ];
    }

    private function fetchHeatmap(): array
    {
        return Order::query()
            ->where('created_at', '>=', now()->subHours(6))
            ->whereNotNull('pickup_latitude')
            ->get(['pickup_latitude', 'pickup_longitude'])
            ->map(fn (Order $o) => [
                (float) $o->pickup_latitude,
                (float) $o->pickup_longitude,
                0.6,
            ])
            ->toArray();
    }
}
