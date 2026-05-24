<?php

namespace App\Models\Concerns;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;

/** @mixin Order */
trait OrderStateTransitions
{
    public function canTransitionTo(OrderStatus $newStatus): bool
    {
        return $this->status->canTransitionTo($newStatus);
    }

    public function transitionTo(OrderStatus $newStatus, ?int $changedBy = null, ?string $note = null, ?float $latitude = null, ?float $longitude = null): bool
    {
        if (! $this->canTransitionTo($newStatus)) {
            return false;
        }

        $previousStatus = $this->status;

        $this->status = $newStatus;

        match ($newStatus) {
            OrderStatus::ASSIGNED => $this->assigned_at = now(),
            OrderStatus::PICKED_UP => $this->picked_up_at = now(),
            OrderStatus::DELIVERED => $this->delivered_at = now(),
            OrderStatus::CANCELLED => $this->cancelled_at = now(),
            default => null,
        };

        $this->save();

        $this->statusHistories()->create([
            'status' => $newStatus,
            'previous_status' => $previousStatus,
            'changed_by' => $changedBy,
            'note' => $note,
            'latitude' => $latitude,
            'longitude' => $longitude,
        ]);

        return true;
    }

    public function assign(User $courier, ?int $changedBy = null): bool
    {
        if (! $courier->canAcceptOrders()) {
            return false;
        }

        $this->setAttribute('courier_id', $courier->id);

        return $this->transitionTo(OrderStatus::ASSIGNED, $changedBy);
    }

    public function markAsPickedUp(?int $changedBy = null, ?float $latitude = null, ?float $longitude = null): bool
    {
        return $this->transitionTo(OrderStatus::PICKED_UP, $changedBy, null, $latitude, $longitude);
    }

    public function markAsDelivered(?int $changedBy = null, ?float $latitude = null, ?float $longitude = null): bool
    {
        return DB::transaction(function () use ($changedBy, $latitude, $longitude) {
            $result = $this->transitionTo(OrderStatus::DELIVERED, $changedBy, null, $latitude, $longitude);

            $assignedCourier = $this->courier()->first();

            if ($result && $assignedCourier) {
                $assignedCourier->incrementTotalOrders();
                app(WalletService::class)->creditCourierForDelivery($assignedCourier, (float) $this->getAttribute('courier_earnings'), $this);
            }

            return $result;
        });
    }

    public function cancel(string $reason, ?int $changedBy = null): bool
    {
        $this->setAttribute('cancellation_reason', $reason);

        return $this->transitionTo(OrderStatus::CANCELLED, $changedBy, $reason);
    }
}
