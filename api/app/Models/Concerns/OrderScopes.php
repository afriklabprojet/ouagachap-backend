<?php

namespace App\Models\Concerns;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Builder;

trait OrderScopes
{
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', OrderStatus::PENDING);
    }

    public function scopeAssigned(Builder $query): Builder
    {
        return $query->where('status', OrderStatus::ASSIGNED);
    }

    public function scopeInProgress(Builder $query): Builder
    {
        return $query->whereIn('status', OrderStatus::activeStatuses());
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', OrderStatus::DELIVERED);
    }

    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', OrderStatus::CANCELLED);
    }

    public function scopeForClient(Builder $query, int $clientId): Builder
    {
        return $query->where('client_id', $clientId);
    }

    public function scopeForCourier(Builder $query, int $courierId): Builder
    {
        return $query->where('courier_id', $courierId);
    }

    public function scopeAvailableForCouriers(Builder $query): Builder
    {
        return $query->where('status', OrderStatus::PENDING)
            ->whereNull('courier_id');
    }
}
