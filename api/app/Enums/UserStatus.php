<?php

namespace App\Enums;

enum UserStatus: string
{
    case ACTIVE    = 'active';
    case PENDING   = 'pending';
    case SUSPENDED = 'suspended';
    case REJECTED  = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE    => 'Actif',
            self::PENDING   => 'En attente',
            self::SUSPENDED => 'Suspendu',
            self::REJECTED  => 'Rejeté',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ACTIVE    => 'success',
            self::PENDING   => 'warning',
            self::SUSPENDED => 'danger',
            self::REJECTED  => 'gray',
        };
    }
}
