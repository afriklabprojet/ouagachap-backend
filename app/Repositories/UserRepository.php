<?php

namespace App\Repositories;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

/**
 * Repository pour les opérations complexes sur les utilisateurs
 */
class UserRepository
{
    protected string $cachePrefix = 'users:';
    protected int $cacheTtl = 300; // 5 minutes

    /**
     * Récupérer les coursiers disponibles
     */
    public function getAvailableCouriers(): Collection
    {
        return User::where('role', UserRole::COURIER)
            ->where('status', UserStatus::ACTIVE)
            ->where('is_available', true)
            ->whereNotNull('current_latitude')
            ->whereNotNull('current_longitude')
            ->select(['id', 'name', 'phone', 'current_latitude', 'current_longitude', 'vehicle_type', 'average_rating'])
            ->get();
    }

    /**
     * Trouver les coursiers proches d'une position
     */
    public function findNearbyCouriers(
        float $latitude,
        float $longitude,
        float $radiusKm = 5,
        int $limit = 10
    ): Collection {
        $earthRadius = 6371; // km

        return User::where('role', UserRole::COURIER)
            ->where('status', UserStatus::ACTIVE)
            ->where('is_available', true)
            ->whereNotNull('current_latitude')
            ->whereNotNull('current_longitude')
            ->selectRaw("
                id, name, phone, vehicle_type, average_rating,
                current_latitude, current_longitude,
                ({$earthRadius} * acos(
                    cos(radians(?)) * cos(radians(current_latitude)) *
                    cos(radians(current_longitude) - radians(?)) +
                    sin(radians(?)) * sin(radians(current_latitude))
                )) AS distance
            ", [$latitude, $longitude, $latitude])
            ->having('distance', '<=', $radiusKm)
            ->orderBy('distance')
            ->limit($limit)
            ->get();
    }

    /**
     * Récupérer les clients avec pagination
     */
    public function getClients(int $perPage = 15, ?string $search = null): LengthAwarePaginator
    {
        $query = User::where('role', UserRole::CLIENT)
            ->orderByDesc('created_at');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage);
    }

    /**
     * Récupérer les coursiers avec pagination
     */
    public function getCouriers(int $perPage = 15, ?string $search = null, ?string $status = null): LengthAwarePaginator
    {
        $query = User::where('role', UserRole::COURIER)
            ->withCount(['courierOrders as completed_orders' => fn($q) => $q->where('status', 'delivered')])
            ->orderByDesc('created_at');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('vehicle_plate', 'like', "%{$search}%");
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        return $query->paginate($perPage);
    }

    /**
     * Statistiques utilisateurs pour le dashboard
     */
    public function getDashboardStats(): array
    {
        return Cache::remember($this->cachePrefix . 'dashboard_stats', $this->cacheTtl, function () {
            return [
                'clients' => [
                    'total' => User::where('role', UserRole::CLIENT)->count(),
                    'active' => User::where('role', UserRole::CLIENT)
                        ->where('status', UserStatus::ACTIVE)->count(),
                    'new_today' => User::where('role', UserRole::CLIENT)
                        ->whereDate('created_at', today())->count(),
                    'new_this_month' => User::where('role', UserRole::CLIENT)
                        ->where('created_at', '>=', now()->startOfMonth())->count(),
                ],
                'couriers' => [
                    'total' => User::where('role', UserRole::COURIER)->count(),
                    'active' => User::where('role', UserRole::COURIER)
                        ->where('status', UserStatus::ACTIVE)->count(),
                    'available_now' => User::where('role', UserRole::COURIER)
                        ->where('status', UserStatus::ACTIVE)
                        ->where('is_available', true)->count(),
                    'pending_approval' => User::where('role', UserRole::COURIER)
                        ->where('status', UserStatus::PENDING)->count(),
                ],
            ];
        });
    }

    /**
     * Top coursiers par note et commandes
     */
    public function getTopCouriers(int $limit = 10): Collection
    {
        return User::where('role', UserRole::COURIER)
            ->where('status', UserStatus::ACTIVE)
            ->where('total_orders', '>', 0)
            ->orderByDesc('average_rating')
            ->orderByDesc('total_orders')
            ->limit($limit)
            ->get(['id', 'name', 'phone', 'average_rating', 'total_orders', 'total_ratings']);
    }

    /**
     * Invalider le cache
     */
    public function clearCache(): void
    {
        Cache::forget($this->cachePrefix . 'dashboard_stats');
    }
}
