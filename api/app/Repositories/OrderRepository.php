<?php

namespace App\Repositories;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class OrderRepository
{
    public function getPending(): Collection
    {
        return Order::query()
            ->where('status', OrderStatus::PENDING)
            ->with(['client', 'zone'])
            ->orderByDesc('created_at')
            ->get();
    }

    public function getActive(): Collection
    {
        return Order::query()
            ->whereIn('status', [
                OrderStatus::ASSIGNED,
                OrderStatus::ACCEPTED,
                OrderStatus::PICKING_UP,
                OrderStatus::PICKED_UP,
                OrderStatus::IN_TRANSIT,
            ])
            ->with(['client', 'courier', 'zone'])
            ->orderByDesc('updated_at')
            ->get();
    }

    public function forClient(string $clientId, int $perPage = 15): LengthAwarePaginator
    {
        return Order::query()
            ->where('client_id', $clientId)
            ->with(['courier', 'zone'])
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function forCourier(string $courierId, int $perPage = 15): LengthAwarePaginator
    {
        return Order::query()
            ->where('courier_id', $courierId)
            ->with(['client', 'zone'])
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function search(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Order::query()->with(['client', 'courier', 'zone']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['zone_id'])) {
            $query->where('zone_id', $filters['zone_id']);
        }

        if (!empty($filters['search'])) {
            $term = $filters['search'];
            $query->where(function ($q) use ($term) {
                $q->where('order_number', 'like', "%{$term}%")
                  ->orWhere('pickup_address', 'like', "%{$term}%")
                  ->orWhere('dropoff_address', 'like', "%{$term}%");
            });
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    public function findWithRelations(string $id): ?Order
    {
        return Order::with(['client', 'courier', 'zone', 'statusHistories', 'payment'])
            ->find($id);
    }

    public function countByStatus(): array
    {
        return Order::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();
    }
}
