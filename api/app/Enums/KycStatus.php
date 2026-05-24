<?php

namespace App\Enums;

enum KycStatus: string
{
    case NONE     = 'none';
    case PENDING  = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return match($this) {
            self::NONE     => 'Non soumis',
            self::PENDING  => 'En attente de validation',
            self::APPROVED => 'Validé',
            self::REJECTED => 'Rejeté',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::NONE     => 'gray',
            self::PENDING  => 'warning',
            self::APPROVED => 'success',
            self::REJECTED => 'danger',
        };
    }
}
