<?php

namespace App\Models\Concerns;

use App\Enums\OrderStatus;
use App\Models\Order;

/** @mixin Order */
trait OrderHelpers
{
    public static function generateOrderNumber(): string
    {
        $prefix = 'OC';
        $date = now()->format('ymd');
        $random = strtoupper(substr(uniqid(), -4));

        return "{$prefix}{$date}{$random}";
    }

    public function isPending(): bool
    {
        return $this->status === OrderStatus::PENDING;
    }

    public function isAssigned(): bool
    {
        return $this->status === OrderStatus::ASSIGNED;
    }

    public function isInProgress(): bool
    {
        return in_array($this->status, OrderStatus::activeStatuses());
    }

    public function isCompleted(): bool
    {
        return $this->status === OrderStatus::DELIVERED;
    }

    public function isCancelled(): bool
    {
        return $this->status === OrderStatus::CANCELLED;
    }

    public function rateClient(int $rating, ?string $review = null): void
    {
        $this->update([
            'client_rating' => $rating,
            'client_review' => $review,
        ]);

        $client = $this->client()->first();

        if ($client) {
            $client->updateRating($rating);
        }
    }

    public function rateCourier(int $rating, ?string $review = null): void
    {
        $this->update([
            'courier_rating' => $rating,
            'courier_review' => $review,
        ]);

        $courier = $this->courier()->first();

        if ($courier) {
            $courier->updateRating($rating);
        }
    }
}
