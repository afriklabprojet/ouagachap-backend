<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case ORANGE_MONEY = 'orange_money';
    case MOOV_MONEY = 'moov_money';
    case CASH = 'cash';

    public function label(): string
    {
        return match ($this) {
            self::ORANGE_MONEY => 'Orange Money',
            self::MOOV_MONEY => 'Moov Money',
            self::CASH => 'Espèces',
        };
    }
}
