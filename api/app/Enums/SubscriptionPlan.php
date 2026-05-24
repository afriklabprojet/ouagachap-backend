<?php

namespace App\Enums;

enum SubscriptionPlan: string
{
    case BASIC   = 'basic';
    case PREMIUM = 'premium';

    /** Prix mensuel en XOF */
    public function priceXof(): int
    {
        return match($this) {
            self::BASIC   => 3500,
            self::PREMIUM => 7000,
        };
    }

    /** Remise appliquée par livraison (XOF) */
    public function discountXof(): int
    {
        return match($this) {
            self::BASIC   => 150,
            self::PREMIUM => 300,
        };
    }

    /** Dispatch prioritaire pour les commandes Premium */
    public function hasPriorityDispatch(): bool
    {
        return $this === self::PREMIUM;
    }

    public function label(): string
    {
        return match($this) {
            self::BASIC   => 'CHAP Pass Basic',
            self::PREMIUM => 'CHAP Pass Premium',
        };
    }
}
