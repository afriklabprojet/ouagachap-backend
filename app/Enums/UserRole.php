<?php

namespace App\Enums;

enum UserRole: string
{
    case CLIENT = 'client';
    case COURIER = 'courier';
    case ADMIN = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::CLIENT => 'Client',
            self::COURIER => 'Coursier',
            self::ADMIN => 'Administrateur',
        };
    }
}
