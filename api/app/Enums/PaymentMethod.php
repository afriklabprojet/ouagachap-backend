<?php

namespace App\Enums;

enum PaymentMethod: string
{
    // Opérateurs Mobile Money Burkina Faso (Sappay)
    case ORANGE_MONEY  = 'orange_money';
    case MOOV_MONEY    = 'moov_money';
    case TELECEL_MONEY = 'telecel_money';
    case CORIS_MONEY   = 'coris_money';
    // Autres méthodes en ligne
    case WAVE          = 'wave';
    case MTN_MONEY     = 'mtn_money';
    case DJAMO         = 'djamo';
    // Paiement à la livraison
    case CASH          = 'cash';

    public function label(): string
    {
        return match ($this) {
            self::ORANGE_MONEY  => 'Orange Money',
            self::MOOV_MONEY    => 'Moov Money',
            self::TELECEL_MONEY => 'Telecel Money',
            self::CORIS_MONEY   => 'Coris Money',
            self::WAVE          => 'Wave',
            self::MTN_MONEY     => 'MTN Mobile Money',
            self::DJAMO         => 'Djamo',
            self::CASH          => 'Espèces',
        };
    }

    public function isSappaySupported(): bool
    {
        return in_array($this, [
            self::ORANGE_MONEY,
            self::MOOV_MONEY,
            self::TELECEL_MONEY,
            self::CORIS_MONEY,
        ]);
    }

    public function isOnline(): bool
    {
        return $this !== self::CASH;
    }
}
