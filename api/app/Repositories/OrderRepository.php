<?php

namespace App\Repositories;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

/**
 * Repository pour les opérations complexes sur les commandes
 */
class OrderRepository
{
    protected string $cachePrefix = 'orders:';
    protected int $cacheTtl = 300; // 5 minutes

    /**
     * Récupérer les commandes d'un client avec filtres
     */
    public function getClientOrders(
        User $client,
        ?string $status = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        $query = Order::with(['courier:id,name,phone,average_rating', 'zone:id,name'])
            ->where('client_id', $client->id)
            ->orderByDesc('created_at');

        if ($status) {
            $query->where('status', $status);
        }

        return $query->paginate($perPage);
    }

    /**
     * Récupérer les commandes d'un coursier avec filtres
     */
    public function getCourierOrders(
        User $courier,
        ?string $status = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        $query = Order::with(['client:id,name,phone', 'zone:id,name'])
            ->where('courier_id', $courier->id)
            ->orderByDesc('created_at');

        if ($status) {
            $query->where('status', $status);
        }

        return $query->paginate($perPage);
    }

    /**
     * Récupérer les commandes disponibles pour les coursiers
     */
    public function getAvailableOrders(?int $zoneId = null, int $perPage = 20): LengthAwarePaginator
    {
        $query = Order::with(['client:id,name,phone', 'zone:id,name,base_price,price_per_km'])
            ->where('status', OrderStatus::PENDING)
            ->whereNull('courier_id')
            ->orderByDesc('created_at');

        if ($zoneId) {
            $query->where('zone_id', $zoneId);
        }

        return $query->paginate($perPage);
    }

    /**
     * Récupérer les commandes proches d'une position
     */
    public function getNearbyOrders(
        float $latitude,
        float $longitude,
        float $radiusKm = 5,
        int $limit = 20
    ): Collection {
        // Formule Haversine pour SQLite
        $earthRadius = 6371; // km

        return Order::with(['client:id,name,phone', 'zone:id,name'])
            ->where('status', OrderStatus::PENDING)
            ->whereNull('courier_id')
            ->selectRaw("
                *,
                ({$earthRadius} * acos(
                    cos(radians(?)) * cos(radians(pickup_latitude)) *
                    cos(radians(pickup_longitude) - radians(?)) +
                    sin(radians(?)) * sin(radians(pickup_latitude))
                )) AS distance
            ", [$latitude, $longitude, $latitude])
            ->having('distance', '<=', $radiusKm)
            ->orderBy('distance')
            ->limit($limit)
            ->get();
    }

    /**
     * Récupérer la commande active d'un coursier
     */
    public function getCourierActiveOrder(User $courier): ?Order
    {
        return Order::with(['client:id,name,phone', 'zone:id,name'])
            ->where('courier_id', $courier->id)
            ->whereIn('status', [
                OrderStatus::ASSIGNED,
                OrderStatus::PICKED_UP,
            ])
            ->first();
    }

    /**
     * Statistiques des commandes pour le dashboard
     */
    public function getDashboardStats(): array
    {
        return Cache::remember($this->cachePrefix . 'dashboard_stats', $this->cacheTtl, function () {
            $today = now()->startOfDay();
            $thisMonth = now()->startOfMonth();

            return [
                'today' => [
                    'total' => Order::whereDate('created_at', $today)->count(),
                    'pending' => Order::where('status', OrderStatus::PENDING)
                        ->whereDate('created_at', $today)->count(),
                    'delivered' => Order::where('status', OrderStatus::DELIVERED)
                        ->whereDate('created_at', $today)->count(),
                    'cancelled' => Order::where('status', OrderStatus::CANCELLED)
                        ->whereDate('created_at', $today)->count(),
                    'revenue' => Order::where('status', OrderStatus::DELIVERED)
                        ->whereDate('created_at', $today)->sum('total_price'),
                ],
                'this_month' => [
                    'total' => Order::where('created_at', '>=', $thisMonth)->count(),
                    'delivered' => Order::where('status', OrderStatus::DELIVERED)
                        ->where('created_at', '>=', $thisMonth)->count(),
                    'revenue' => Order::where('status', OrderStatus::DELIVERED)
                        ->where('created_at', '>=', $thisMonth)->sum('total_price'),
                ],
                'by_status' => [
                    'pending' => Order::where('status', OrderStatus::PENDING)->count(),
                    'assigned' => Order::where('status', OrderStatus::ASSIGNED)->count(),
                    'picked_up' => Order::where('status', OrderStatus::PICKED_UP)->count(),
                    'delivered' => Order::where('status', OrderStatus::DELIVERED)->count(),
                    'cancelled' => Order::where('status', OrderStatus::CANCELLED)->count(),
                ],
            ];
        });
    }

    /**
     * Invalider le cache des stats
     */
    public function clearDashboardCache(): void
    {
        Cache::forget($this->cachePrefix . 'dashboard_stats');
    }

    /**
     * Récupérer une commande avec toutes ses relations
     */
    public function findWithRelations(string $id): ?Order
    {
        return Order::with([
            'client:id,name,phone,email,average_rating',
            'courier:id,name,phone,vehicle_type,vehicle_plate,average_rating,current_latitude,current_longitude',
            'zone:id,name,code,base_price,price_per_km',
            'statusHistories' => fn($q) => $q->orderBy('created_at'),
            'payments' => fn($q) => $q->latest(),
        ])->find($id);
    }

    /**
     * Récupérer les colis entrants pour un destinataire
     */
    public function getIncomingOrders(User $recipient, int $perPage = 15): LengthAwarePaginator
    {
        return Order::with(['client:id,name,phone', 'courier:id,name,phone,current_latitude,current_longitude'])
            ->where('recipient_user_id', $recipient->id)
            ->whereIn('status', [
                OrderStatus::PENDING,
                OrderStatus::ASSIGNED,
                OrderStatus::PICKED_UP,
            ])
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }
}
