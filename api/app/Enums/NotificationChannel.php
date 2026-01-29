<?php

namespace App\Enums;

/**
 * Canaux de notifications push
 * Chaque canal a sa propre priorité et configuration
 */
enum NotificationChannel: string
{
    // Haute priorité - Temps réel
    case NEW_ORDER = 'new_orders';           // Nouvelles commandes pour coursiers
    case ORDER_STATUS = 'order_status';       // Changements de statut pour clients
    case URGENT = 'urgent';                   // Alertes critiques
    
    // Priorité moyenne
    case PAYMENTS = 'payments';               // Paiements et gains
    case CHAT = 'chat';                       // Messages de chat
    
    // Priorité normale
    case PROMOTIONS = 'promotions';           // Offres et promotions
    case GENERAL = 'general';                 // Notifications générales
    case RATINGS = 'ratings';                 // Évaluations et avis

    /**
     * Nom affiché du canal
     */
    public function label(): string
    {
        return match ($this) {
            self::NEW_ORDER => 'Nouvelles commandes',
            self::ORDER_STATUS => 'Statut des commandes',
            self::URGENT => 'Alertes urgentes',
            self::PAYMENTS => 'Paiements et gains',
            self::CHAT => 'Messages',
            self::PROMOTIONS => 'Promotions',
            self::GENERAL => 'Notifications générales',
            self::RATINGS => 'Évaluations',
        };
    }

    /**
     * Description du canal
     */
    public function description(): string
    {
        return match ($this) {
            self::NEW_ORDER => 'Notifications pour les nouvelles courses disponibles',
            self::ORDER_STATUS => 'Mises à jour du statut de vos livraisons',
            self::URGENT => 'Alertes importantes nécessitant une action immédiate',
            self::PAYMENTS => 'Confirmations de paiement et crédits de gains',
            self::CHAT => 'Messages entre clients et coursiers',
            self::PROMOTIONS => 'Offres spéciales et codes promo',
            self::GENERAL => 'Informations générales sur votre compte',
            self::RATINGS => 'Nouvelles évaluations reçues',
        };
    }

    /**
     * Priorité Android (high = foreground, normal = background)
     */
    public function androidPriority(): string
    {
        return match ($this) {
            self::NEW_ORDER, self::ORDER_STATUS, self::URGENT, self::CHAT => 'high',
            default => 'normal',
        };
    }

    /**
     * Priorité iOS (10 = immédiate, 5 = normale)
     */
    public function iosPriority(): string
    {
        return match ($this) {
            self::NEW_ORDER, self::ORDER_STATUS, self::URGENT => '10',
            default => '5',
        };
    }

    /**
     * Son personnalisé (null = default)
     */
    public function sound(): ?string
    {
        return match ($this) {
            self::NEW_ORDER => 'new_order.mp3',
            self::URGENT => 'urgent_alert.mp3',
            self::CHAT => 'message.mp3',
            default => 'default',
        };
    }

    /**
     * Icône emoji pour la notification
     */
    public function icon(): string
    {
        return match ($this) {
            self::NEW_ORDER => '📦',
            self::ORDER_STATUS => '🏍️',
            self::URGENT => '🚨',
            self::PAYMENTS => '💰',
            self::CHAT => '💬',
            self::PROMOTIONS => '🎁',
            self::GENERAL => 'ℹ️',
            self::RATINGS => '⭐',
        };
    }

    /**
     * Est-ce que ce canal peut réveiller l'écran?
     */
    public function wakeScreen(): bool
    {
        return match ($this) {
            self::NEW_ORDER, self::URGENT => true,
            default => false,
        };
    }

    /**
     * Catégorie Android pour les actions
     */
    public function androidCategory(): string
    {
        return match ($this) {
            self::NEW_ORDER => 'alarm',
            self::URGENT => 'alarm',
            self::CHAT => 'message',
            default => 'promo',
        };
    }
}
