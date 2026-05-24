<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PENDING = 'pending';
    case ASSIGNED = 'assigned';
    case ACCEPTED = 'accepted';
    case PICKING_UP = 'picking_up';
    case PICKED_UP = 'picked_up';
    case IN_TRANSIT = 'in_transit';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'En attente',
            self::ASSIGNED => 'Assignée',
            self::ACCEPTED => 'Acceptée',
            self::PICKING_UP => 'En route collecte',
            self::PICKED_UP => 'Récupérée',
            self::IN_TRANSIT => 'En transit',
            self::DELIVERED => 'Livrée',
            self::CANCELLED => 'Annulée',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::ASSIGNED => 'info',
            self::ACCEPTED => 'info',
            self::PICKING_UP => 'primary',
            self::PICKED_UP => 'primary',
            self::IN_TRANSIT => 'primary',
            self::DELIVERED => 'success',
            self::CANCELLED => 'danger',
        };
    }

    public static function allowedTransitions(OrderStatus $from): array
    {
        return match ($from) {
            self::PENDING => [self::ASSIGNED, self::CANCELLED],
            self::ASSIGNED => [self::ACCEPTED, self::PICKING_UP, self::PICKED_UP, self::CANCELLED],
            self::ACCEPTED => [self::PICKING_UP, self::PICKED_UP, self::CANCELLED],
            self::PICKING_UP => [self::PICKED_UP, self::CANCELLED],
            self::PICKED_UP => [self::IN_TRANSIT, self::DELIVERED, self::CANCELLED],
            self::IN_TRANSIT => [self::DELIVERED, self::CANCELLED],
            self::DELIVERED => [],
            self::CANCELLED => [],
        };
    }

    /**
     * Statuts considérés comme "en cours" (livraison active)
     */
    public static function activeStatuses(): array
    {
        return [
            self::ASSIGNED,
            self::ACCEPTED,
            self::PICKING_UP,
            self::PICKED_UP,
            self::IN_TRANSIT,
        ];
    }

    public function canTransitionTo(OrderStatus $to): bool
    {
        return in_array($to, self::allowedTransitions($this));
    }
}
