<?php

namespace App\Enums;

enum NotificationType: string
{
    case ORDER_CREATED = 'order_created';
    case ORDER_CONFIRMED = 'order_confirmed';
    case ORDER_ASSIGNED = 'order_assigned';
    case ORDER_PICKED_UP = 'order_picked_up';
    case ORDER_IN_TRANSIT = 'order_in_transit';
    case ORDER_DELIVERED = 'order_delivered';
    case ORDER_CANCELLED = 'order_cancelled';
    case NEW_ORDER_AVAILABLE = 'new_order_available';
    case ORDER_ACCEPTED = 'order_accepted';
    case PAYMENT_RECEIVED = 'payment_received';
    case PAYMENT_FAILED = 'payment_failed';
    case WALLET_CREDITED = 'wallet_credited';
    case WITHDRAWAL_REQUESTED = 'withdrawal_requested';
    case WITHDRAWAL_APPROVED = 'withdrawal_approved';
    case WITHDRAWAL_COMPLETED = 'withdrawal_completed';
    case WITHDRAWAL_REJECTED = 'withdrawal_rejected';
    case PROMOTIONAL = 'promotional';

    public function getTitle(): string
    {
        return match ($this) {
            self::ORDER_CREATED => 'Nouvelle commande',
            self::ORDER_CONFIRMED => 'Commande confirmée',
            self::ORDER_ASSIGNED => 'Coursier assigné',
            self::ORDER_PICKED_UP => 'Colis récupéré',
            self::ORDER_IN_TRANSIT => 'Livraison en cours',
            self::ORDER_DELIVERED => 'Livraison effectuée',
            self::ORDER_CANCELLED => 'Commande annulée',
            self::NEW_ORDER_AVAILABLE => 'Nouvelle commande disponible',
            self::ORDER_ACCEPTED => 'Commande acceptée',
            self::PAYMENT_RECEIVED => 'Paiement reçu',
            self::PAYMENT_FAILED => 'Échec du paiement',
            self::WALLET_CREDITED => 'Portefeuille crédité',
            self::WITHDRAWAL_REQUESTED => 'Demande de retrait',
            self::WITHDRAWAL_APPROVED => 'Retrait approuvé',
            self::WITHDRAWAL_COMPLETED => 'Retrait effectué',
            self::WITHDRAWAL_REJECTED => 'Retrait rejeté',
            self::PROMOTIONAL => 'Offre spéciale',
        };
    }

    public function getChannel(): array
    {
        return match ($this) {
            self::ORDER_DELIVERED, self::WITHDRAWAL_COMPLETED => ['push', 'sms'],
            default => ['push'],
        };
    }

    public function isOptional(): bool
    {
        return match ($this) {
            self::PROMOTIONAL => true,
            default => false,
        };
    }

    public function getDefaultMessage(array $data = []): string
    {
        return match ($this) {
            self::ORDER_CREATED => 'Votre commande ' . ($data['order_number'] ?? '?') . ' a été créée.',
            self::ORDER_CONFIRMED => 'Votre commande ' . ($data['order_number'] ?? '?') . ' a été confirmée.',
            self::ORDER_ASSIGNED => 'Un coursier a été assigné à votre commande.',
            self::ORDER_PICKED_UP => 'Votre colis a été récupéré.',
            self::ORDER_IN_TRANSIT => 'Votre commande est en cours de livraison.',
            self::ORDER_DELIVERED => 'Votre commande a été livrée avec succès.',
            self::ORDER_CANCELLED => 'Votre commande a été annulée.',
            self::NEW_ORDER_AVAILABLE => 'Nouvelle commande disponible à ' . ($data['distance'] ?? '?') . ' km.',
            self::ORDER_ACCEPTED => 'Votre commande a été acceptée par le coursier.',
            self::PAYMENT_RECEIVED => 'Paiement de ' . ($data['amount'] ?? '?') . ' FCFA reçu.',
            self::PAYMENT_FAILED => 'Le paiement a échoué. Veuillez réessayer.',
            self::WALLET_CREDITED => 'Votre portefeuille a été crédité de ' . ($data['amount'] ?? '?') . ' FCFA.',
            self::WITHDRAWAL_REQUESTED => 'Votre demande de retrait a été enregistrée.',
            self::WITHDRAWAL_APPROVED => 'Votre retrait a été approuvé.',
            self::WITHDRAWAL_COMPLETED => 'Retrait de ' . ($data['amount'] ?? '?') . ' FCFA effectué.',
            self::WITHDRAWAL_REJECTED => 'Votre demande de retrait a été rejetée.',
            self::PROMOTIONAL => $data['message'] ?? 'Découvrez nos offres spéciales !',
        };
    }
}
