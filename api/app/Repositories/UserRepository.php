<?php

namespace App\Repositories;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use App\Traits\CalculatesDistance;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class UserRepository
{
    use CalculatesDistance;

    protected string $cachePrefix = 'users:';
    protected int $cacheTtl = 300;

    public function getAvailableCouriers(): Collection
    {
        return User::query()
            ->where('role', UserRole::COURIER)
            ->where('status', UserStatus::ACTIVE)
            ->where('is_available', true)
            ->whereNotNull('current_latitude')
            ->whereNotNull('current_longitude')
            ->get();
    }

    public function findNearbyCouriers(float $latitude, float $longitude, float $radiusKm = 10, int $limit = 10): Collection
    {
        [$haversine, $bindings] = $this->haversineExpression(
            $latitude, $longitude, 'current_latitude', 'current_longitude',
        );

        return User::query()
            ->selectRaw("*, {$haversine} AS distance", $bindings)
            ->where('role', UserRole::COURIER)
            ->where('status', UserStatus::ACTIVE)
            ->where('is_available', true)
            ->whereNotNull('current_latitude')
            ->whereNotNull('current_longitude')
            ->having('distance', '<=', $radiusKm)
            ->orderBy('distance')
            ->limit($limit)
            ->get();
    }

    public function getClients(int $perPage = 15, ?string $search = null): LengthAwarePaginator
    {
        $query = User::query()
            ->where('role', UserRole::CLIENT)
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

    public function getCouriers(int $perPage = 15, ?string $search = null, ?string $status = null): LengthAwarePaginator
    {
        $query = User::query()
            ->where('role', UserRole::COURIER)
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

    public function getDashboardStats(): array
    {
        return Cache::remember($this->cachePrefix . 'dashboard_stats', $this->cacheTtl, function () {
            return [
                'clients' => [
                    'total' => User::where('role', UserRole::CLIENT)->count(),
                    'active' => User::where('role', UserRole::CLIENT)->where('status', UserStatus::ACTIVE)->count(),
                    'new_today' => User::where('role', UserRole::CLIENT)->whereDate('created_at', today())->count(),
                ],
                'couriers' => [
                    'total' => User::where('role', UserRole::COURIER)->count(),
                    'active' => User::where('role', UserRole::COURIER)->where('status', UserStatus::ACTIVE)->count(),
                    'available_now' => User::where('role', UserRole::COURIER)->where('status', UserStatus::ACTIVE)->where('is_available', true)->count(),
                    'pending_approval' => User::where('role', UserRole::COURIER)->where('status', UserStatus::PENDING)->count(),
                ],
            ];
        });
    }

    public function getTopCouriers(int $limit = 10): Collection
    {
        return User::query()
            ->where('role', UserRole::COURIER)
            ->where('status', UserStatus::ACTIVE)
            ->where('total_orders', '>', 0)
            ->orderByDesc('average_rating')
            ->orderByDesc('total_orders')
            ->limit($limit)
            ->get();
    }

    public function clearCache(): void
    {
        Cache::forget($this->cachePrefix . 'dashboard_stats');
    }
}
