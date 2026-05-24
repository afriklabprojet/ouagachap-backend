<?php

namespace App\Enums;

enum NotificationChannel: string
{
    case GENERAL = 'general';
    case ORDER_STATUS = 'order_status';
    case NEW_ORDER = 'new_order';
    case PAYMENTS = 'payments';
    case CHAT = 'chat';
    case RATINGS = 'ratings';
    case PROMOTIONS = 'promotions';
    case URGENT = 'urgent';

    public function androidPriority(): string
    {
        return match ($this) {
            self::URGENT, self::NEW_ORDER, self::CHAT => 'high',
            default => 'normal',
        };
    }

    public function sound(): ?string
    {
        return match ($this) {
            self::URGENT => 'urgent_sound',
            self::NEW_ORDER => 'new_order_sound',
            self::CHAT => 'chat_sound',
            default => null,
        };
    }

    public function wakeScreen(): bool
    {
        return match ($this) {
            self::URGENT, self::NEW_ORDER => true,
            default => false,
        };
    }

    public function iosPriority(): string
    {
        return match ($this) {
            self::URGENT, self::NEW_ORDER, self::CHAT => '10',
            default => '5',
        };
    }

    /**
     * Les canaux data-only sont envoyés sans notification payload FCM.
     * Le handler background Flutter prend le contrôle total (fullScreenIntent,
     * son natif, réveil écran). Obligatoire pour réveiller l'écran en veille.
     */
    public function isDataOnly(): bool
    {
        return match ($this) {
            self::NEW_ORDER, self::URGENT => true,
            default => false,
        };
    }
}
