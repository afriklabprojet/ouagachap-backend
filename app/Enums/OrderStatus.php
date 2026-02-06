<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PENDING = 'pending';
    case ASSIGNED = 'assigned';
    case PICKED_UP = 'picked_up';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'En attente',
            self::ASSIGNED => 'Assignée',
            self::PICKED_UP => 'Récupérée',
            self::DELIVERED => 'Livrée',
            self::CANCELLED => 'Annulée',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::ASSIGNED => 'info',
            self::PICKED_UP => 'primary',
            self::DELIVERED => 'success',
            self::CANCELLED => 'danger',
        };
    }

    public static function allowedTransitions(OrderStatus $from): array
    {
        return match ($from) {
            self::PENDING => [self::ASSIGNED, self::CANCELLED],
            self::ASSIGNED => [self::PICKED_UP, self::CANCELLED],
            self::PICKED_UP => [self::DELIVERED, self::CANCELLED],
            self::DELIVERED => [],
            self::CANCELLED => [],
        };
    }

    public function canTransitionTo(OrderStatus $to): bool
    {
        return in_array($to, self::allowedTransitions($this));
    }
}
