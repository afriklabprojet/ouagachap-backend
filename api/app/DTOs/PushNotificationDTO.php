<?php

namespace App\DTOs;

use App\Enums\NotificationChannel;

/**
 * DTO pour les notifications push
 * Encapsule toutes les données nécessaires pour envoyer une notification
 */
class PushNotificationDTO
{
    public function __construct(
        public readonly string $title,
        public readonly string $body,
        public readonly NotificationChannel $channel,
        public readonly array $data = [],
        public readonly ?string $imageUrl = null,
        public readonly ?string $actionUrl = null,
        public readonly array $actions = [],
        public readonly ?int $badgeCount = null,
        public readonly bool $silent = false,
    ) {}

    /**
     * Créer depuis un template de commande
     */
    public static function forOrderCreated(string $trackingNumber): self
    {
        return new self(
            title: '🎉 Commande créée',
            body: "Votre commande #{$trackingNumber} a été créée. Nous recherchons un coursier.",
            channel: NotificationChannel::ORDER_STATUS,
            data: [
                'type' => 'order_created',
                'order_number' => $trackingNumber,
            ],
        );
    }

    /**
     * Créer notification de nouvelle commande pour coursier
     */
    public static function forNewOrderAvailable(
        string $orderId,
        string $pickupAddress,
        string $deliveryAddress,
        float $distance,
        int $earnings,
    ): self {
        return new self(
            title: '🚀 Nouvelle course disponible!',
            body: "{$pickupAddress} → {$deliveryAddress}\n📍 {$distance} km • 💰 {$earnings} FCFA",
            channel: NotificationChannel::NEW_ORDER,
            data: [
                'type' => 'new_order',
                'order_id' => $orderId,
                'pickup_address' => $pickupAddress,
                'dropoff_address' => $deliveryAddress,
                'distance' => (string) $distance,
                'earnings' => (string) $earnings,
            ],
            actions: [
                ['action' => 'accept', 'title' => 'Accepter'],
                ['action' => 'reject', 'title' => 'Ignorer'],
            ],
        );
    }

    /**
     * Notification d'assignation de coursier
     */
    public static function forOrderAssigned(
        string $orderId,
        string $courierName,
        ?string $courierPhoto = null,
    ): self {
        return new self(
            title: '🏍️ Coursier en route',
            body: "{$courierName} va récupérer votre colis. Suivez-le en temps réel!",
            channel: NotificationChannel::ORDER_STATUS,
            data: [
                'type' => 'order_assigned',
                'order_id' => $orderId,
                'courier_name' => $courierName,
            ],
            imageUrl: $courierPhoto,
            actions: [
                ['action' => 'track', 'title' => 'Suivre'],
                ['action' => 'call', 'title' => 'Appeler'],
            ],
        );
    }

    /**
     * Notification de colis récupéré
     */
    public static function forOrderPickedUp(string $orderId, string $deliveryAddress): self
    {
        return new self(
            title: '📦 Colis en route!',
            body: "Votre colis est en chemin vers {$deliveryAddress}",
            channel: NotificationChannel::ORDER_STATUS,
            data: [
                'type' => 'order_picked_up',
                'order_id' => $orderId,
            ],
            actions: [
                ['action' => 'track', 'title' => 'Suivre la livraison'],
            ],
        );
    }

    /**
     * Notification de livraison effectuée
     */
    public static function forOrderDelivered(
        string $orderId,
        string $recipientName,
        int $totalAmount,
    ): self {
        return new self(
            title: '✅ Livraison effectuée!',
            body: "Votre colis a été remis à {$recipientName}. Merci d'utiliser OUAGA CHAP!",
            channel: NotificationChannel::ORDER_STATUS,
            data: [
                'type' => 'order_delivered',
                'order_id' => $orderId,
                'amount' => (string) $totalAmount,
            ],
            actions: [
                ['action' => 'rate', 'title' => 'Noter le coursier'],
                ['action' => 'order_again', 'title' => 'Commander à nouveau'],
            ],
        );
    }

    /**
     * Notification de gains crédités pour coursier
     */
    public static function forEarningsCredited(
        string $orderId,
        string $orderNumber,
        int $amount,
        int $newBalance,
    ): self {
        return new self(
            title: '💵 Gain crédité!',
            body: "+{$amount} FCFA pour la course #{$orderNumber}\nNouveau solde: {$newBalance} FCFA",
            channel: NotificationChannel::PAYMENTS,
            data: [
                'type' => 'earnings_credited',
                'order_id' => $orderId,
                'amount' => (string) $amount,
                'new_balance' => (string) $newBalance,
            ],
        );
    }

    /**
     * Notification de paiement reçu
     */
    public static function forPaymentReceived(
        string $orderId,
        int $amount,
        string $paymentMethod,
    ): self {
        return new self(
            title: '💰 Paiement confirmé',
            body: "Paiement de {$amount} FCFA reçu via {$paymentMethod}",
            channel: NotificationChannel::PAYMENTS,
            data: [
                'type' => 'payment_received',
                'order_id' => $orderId,
                'amount' => (string) $amount,
            ],
        );
    }

    /**
     * Notification de nouveau message chat
     */
    public static function forNewChatMessage(
        string $orderId,
        string $senderName,
        string $messagePreview,
    ): self {
        return new self(
            title: "💬 {$senderName}",
            body: $messagePreview,
            channel: NotificationChannel::CHAT,
            data: [
                'type' => 'chat_message',
                'order_id' => $orderId,
                'sender_name' => $senderName,
            ],
            actions: [
                ['action' => 'reply', 'title' => 'Répondre'],
            ],
        );
    }

    /**
     * Notification de nouvelle évaluation
     */
    public static function forNewRating(
        int $rating,
        ?string $comment,
        float $newAverage,
    ): self {
        $stars = str_repeat('⭐', $rating);
        $body = $comment
            ? "\"{$comment}\"\nNouvelle moyenne: {$newAverage}/5"
            : "Nouvelle moyenne: {$newAverage}/5";

        return new self(
            title: "Nouvelle évaluation {$stars}",
            body: $body,
            channel: NotificationChannel::RATINGS,
            data: [
                'type' => 'new_rating',
                'rating' => (string) $rating,
                'new_average' => (string) $newAverage,
            ],
        );
    }

    /**
     * Notification promotionnelle
     */
    public static function forPromotion(
        string $title,
        string $body,
        ?string $promoCode = null,
        ?string $imageUrl = null,
    ): self {
        return new self(
            title: "🎁 {$title}",
            body: $body,
            channel: NotificationChannel::PROMOTIONS,
            data: [
                'type' => 'promotion',
                'promo_code' => $promoCode,
            ],
            imageUrl: $imageUrl,
        );
    }

    /**
     * Notification de coursier en approche
     */
    public static function forCourierArriving(string $orderId, int $minutesAway): self
    {
        $message = $minutesAway <= 2
            ? 'Votre coursier est presque arrivé!'
            : "Votre coursier arrive dans environ {$minutesAway} minutes";

        return new self(
            title: '🏍️ Coursier en approche',
            body: $message,
            channel: NotificationChannel::ORDER_STATUS,
            data: [
                'type' => 'courier_arriving',
                'order_id' => $orderId,
                'eta_minutes' => (string) $minutesAway,
            ],
        );
    }

    /**
     * Alerte urgente
     */
    public static function urgent(string $title, string $body, array $data = []): self
    {
        return new self(
            title: "🚨 {$title}",
            body: $body,
            channel: NotificationChannel::URGENT,
            data: array_merge(['type' => 'urgent'], $data),
        );
    }

    /**
     * Convertir en tableau pour FCM
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'channel' => $this->channel->value,
            'data' => $this->data,
            'image_url' => $this->imageUrl,
            'actions' => $this->actions,
            'badge_count' => $this->badgeCount,
            'silent' => $this->silent,
        ];
    }
}
