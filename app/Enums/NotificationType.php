<?php

namespace App\Enums;

enum NotificationType: string
{
    // Commandes
    case ORDER_CREATED = 'order_created';
    case ORDER_CONFIRMED = 'order_confirmed';
    case ORDER_ASSIGNED = 'order_assigned';
    case ORDER_PICKED_UP = 'order_picked_up';
    case ORDER_IN_TRANSIT = 'order_in_transit';
    case ORDER_DELIVERED = 'order_delivered';
    case ORDER_CANCELLED = 'order_cancelled';

    // Coursiers
    case NEW_ORDER_AVAILABLE = 'new_order_available';
    case ORDER_ACCEPTED = 'order_accepted';

    // Paiements
    case PAYMENT_RECEIVED = 'payment_received';
    case PAYMENT_FAILED = 'payment_failed';

    // Portefeuille
    case WALLET_CREDITED = 'wallet_credited';
    case WITHDRAWAL_REQUESTED = 'withdrawal_requested';
    case WITHDRAWAL_APPROVED = 'withdrawal_approved';
    case WITHDRAWAL_COMPLETED = 'withdrawal_completed';
    case WITHDRAWAL_REJECTED = 'withdrawal_rejected';

    // Marketing
    case PROMOTIONAL = 'promotional';

    public function getTitle(): string
    {
        return match($this) {
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

    public function getDefaultMessage(array $data = []): string
    {
        $orderNumber = $data['order_number'] ?? '';
        $amount = $data['amount'] ?? '?';
        $distance = $data['distance'] ?? '?';
        $message = $data['message'] ?? 'Découvrez nos offres !';

        return match($this) {
            self::ORDER_CREATED => "Votre commande #{$orderNumber} a été créée.",
            self::ORDER_CONFIRMED => "Votre commande #{$orderNumber} est confirmée.",
            self::ORDER_ASSIGNED => "Un coursier a été assigné à votre commande.",
            self::ORDER_PICKED_UP => "Votre colis a été récupéré par le coursier.",
            self::ORDER_IN_TRANSIT => "Votre colis est en route vers vous.",
            self::ORDER_DELIVERED => "Votre colis a été livré avec succès !",
            self::ORDER_CANCELLED => "Votre commande a été annulée.",
            self::NEW_ORDER_AVAILABLE => "Nouvelle commande à {$distance} km de vous.",
            self::ORDER_ACCEPTED => "Vous avez accepté la commande #{$orderNumber}.",
            self::PAYMENT_RECEIVED => "Paiement de {$amount} FCFA reçu.",
            self::PAYMENT_FAILED => "Le paiement a échoué. Veuillez réessayer.",
            self::WALLET_CREDITED => "Votre portefeuille a été crédité de {$amount} FCFA.",
            self::WITHDRAWAL_REQUESTED => "Demande de retrait de {$amount} FCFA enregistrée.",
            self::WITHDRAWAL_APPROVED => "Votre retrait de {$amount} FCFA est approuvé.",
            self::WITHDRAWAL_COMPLETED => "Retrait de {$amount} FCFA effectué.",
            self::WITHDRAWAL_REJECTED => "Votre demande de retrait a été rejetée.",
            self::PROMOTIONAL => $message,
        };
    }

    public function getChannel(): array
    {
        return match($this) {
            // Notifications critiques : push + sms
            self::ORDER_DELIVERED,
            self::WITHDRAWAL_COMPLETED => ['push', 'sms'],

            // Notifications importantes : push seulement
            self::ORDER_CREATED,
            self::ORDER_CONFIRMED,
            self::ORDER_ASSIGNED,
            self::ORDER_PICKED_UP,
            self::ORDER_IN_TRANSIT,
            self::ORDER_CANCELLED,
            self::NEW_ORDER_AVAILABLE,
            self::ORDER_ACCEPTED,
            self::PAYMENT_RECEIVED,
            self::PAYMENT_FAILED,
            self::WALLET_CREDITED,
            self::WITHDRAWAL_REQUESTED,
            self::WITHDRAWAL_APPROVED,
            self::WITHDRAWAL_REJECTED => ['push'],

            // Marketing : push optionnel
            self::PROMOTIONAL => ['push'],
        };
    }

    public function isOptional(): bool
    {
        return match($this) {
            self::PROMOTIONAL => true,
            default => false,
        };
    }
}
