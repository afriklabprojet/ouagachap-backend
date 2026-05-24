<?php

namespace App\Services;

use App\Models\User;
use App\Models\Order;
use App\Traits\CalculatesDistance;
use Illuminate\Support\Facades\Log;
use App\Services\CourierMatchingService;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Messaging\AndroidConfig;
use Kreait\Firebase\Messaging\ApnsConfig;
use Kreait\Firebase\Exception\MessagingException;

class PushNotificationService
{
    use CalculatesDistance;

    protected ?Messaging $messaging;

    public function __construct(
        protected CourierMatchingService $courierService,
    ) {
        try {
            $this->messaging = app('firebase.messaging');
        } catch (\Exception $e) {
            Log::warning('Firebase not configured: ' . $e->getMessage());
            $this->messaging = null;
        }
    }

    /**
     * Send notification to a single user
     */
    public function sendToUser(User $user, string $title, string $body, array $data = []): bool
    {
        if (!$user->fcm_token) {
            Log::info("User {$user->id} has no FCM token");
            return false;
        }

        return $this->send($user->fcm_token, $title, $body, $data);
    }

    /**
     * Send notification to multiple users
     */
    public function sendToUsers(array $users, string $title, string $body, array $data = []): array
    {
        $results = ['success' => 0, 'failed' => 0];

        foreach ($users as $user) {
            if ($this->sendToUser($user, $title, $body, $data)) {
                $results['success']++;
            } else {
                $results['failed']++;
            }
        }

        return $results;
    }

    /**
     * Send notification to a topic (all couriers, all clients, etc.)
     */
    public function sendToTopic(string $topic, string $title, string $body, array $data = []): bool
    {
        if (!$this->messaging) {
            Log::warning("Firebase not available - cannot send to topic: {$topic}");
            return false;
        }

        try {
            $message = CloudMessage::withTarget('topic', $topic)
                ->withNotification(Notification::create($title, $body))
                ->withData($data);

            $this->messaging->send($message);

            Log::channel('api')->info("Push sent to topic", [
                'topic' => $topic,
                'title' => $title,
            ]);

            return true;
        } catch (MessagingException $e) {
            Log::error("FCM topic error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Core send method
     */
    protected function send(string $token, string $title, string $body, array $data = []): bool
    {
        if (!$this->messaging) {
            // Log but don't fail - allows development without Firebase
            Log::info("Push notification (Firebase disabled)", [
                'title' => $title,
                'body' => $body,
                'data' => $data,
            ]);
            return true;
        }

        try {
            $message = CloudMessage::withTarget('token', $token)
                ->withNotification(Notification::create($title, $body))
                ->withData($this->sanitizeData($data))
                ->withAndroidConfig($this->androidConfig())
                ->withApnsConfig($this->apnsConfig());

            $this->messaging->send($message);

            Log::channel('api')->info("Push sent", [
                'title' => $title,
                'token_prefix' => substr($token, 0, 20) . '...',
            ]);

            return true;
        } catch (MessagingException $e) {
            Log::error("FCM error: " . $e->getMessage(), [
                'token_prefix' => substr($token, 0, 20) . '...',
            ]);

            // If token is invalid, clear it from user
            if (str_contains($e->getMessage(), 'not a valid FCM registration token')) {
                $this->clearInvalidToken($token);
            }

            return false;
        }
    }

    /**
     * Android specific config
     */
    protected function androidConfig(): AndroidConfig
    {
        return AndroidConfig::fromArray([
            'priority' => 'high',
            'notification' => [
                'channel_id' => 'ouagachap_orders',
                'sound' => 'default',
            ],
        ]);
    }

    /**
     * iOS specific config
     */
    protected function apnsConfig(): ApnsConfig
    {
        return ApnsConfig::fromArray([
            'headers' => [
                'apns-priority' => '10',
            ],
            'payload' => [
                'aps' => [
                    'sound' => 'default',
                    'badge' => 1,
                ],
            ],
        ]);
    }

    /**
     * Ensure all data values are strings (FCM requirement)
     */
    protected function sanitizeData(array $data): array
    {
        return array_map(fn($value) => (string) $value, $data);
    }

    /**
     * Clear invalid FCM token from database
     */
    protected function clearInvalidToken(string $token): void
    {
        User::where('fcm_token', $token)->update([
            'fcm_token' => null,
            'fcm_token_updated_at' => null,
        ]);
    }

    // =========================================================================
    // NOTIFICATION TEMPLATES - ORDER LIFECYCLE
    // =========================================================================

    /**
     * Notify client: Order created
     */
    public function notifyOrderCreated(Order $order): bool
    {
        return $this->sendToUser(
            $order->client,
            '🎉 Commande créée',
            "Votre commande #{$order->order_number} a été créée. Nous recherchons un coursier.",
            [
                'type' => 'order_created',
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ]
        );
    }

    /**
     * Notify client: Courier assigned
     */
    public function notifyOrderAssigned(Order $order): bool
    {
        $courierName = $order->courier?->name ?? 'Un coursier';

        return $this->sendToUser(
            $order->client,
            '🏍️ Coursier assigné',
            "{$courierName} va récupérer votre colis. Suivi en direct disponible.",
            [
                'type' => 'order_assigned',
                'order_id' => $order->id,
                'courier_id' => $order->courier_id,
                'courier_name' => $courierName,
            ]
        );
    }

    /**
     * Notify courier: New order available
     * Includes order type and priority for smart notification sounds on mobile
     */
    public function notifyNewOrderAvailable(Order $order, User $courier): bool
    {
        // Déterminer le titre selon la priorité
        $isUrgent = in_array($order->priority, ['urgent', 'express']);
        $title = $isUrgent
            ? '🚨 URGENT - Course express!' // @codeCoverageIgnoreStart
            : '📦 Nouvelle course disponible'; // @codeCoverageIgnoreEnd

        // Calculer la distance approximative pour le coursier
        $distance = null;
        if ($courier->current_latitude && $courier->current_longitude) {
            $distance = $this->calculateDistanceKm(
                $courier->current_latitude,
                $courier->current_longitude,
                $order->pickup_latitude,
                $order->pickup_longitude
            );
        }

        $body = "Course de {$order->pickup_address} vers {$order->dropoff_address}.";
        if ($distance !== null) {
            $body .= " 📍 " . round($distance, 1) . " km.";
        }
        $body .= " 💰 {$order->courier_earnings} FCFA";

        return $this->sendToUser(
            $courier,
            $title,
            $body,
            [
                'type' => 'new_order',
                'order_id' => (string) $order->id,
                'fee' => (string) $order->courier_earnings,
                'earnings' => (string) $order->courier_earnings,
                // Données pour le son adapté côté mobile
                'order_type' => $order->order_type ?? 'standard',
                'priority' => $order->priority ?? 'normal',
                'is_fragile' => $order->is_fragile ? 'true' : 'false',
                'is_large' => $order->is_large ? 'true' : 'false',
                // Adresses pour l'UI
                'pickup_address' => $order->pickup_address,
                'dropoff_address' => $order->dropoff_address,
                'distance' => $distance !== null ? (string) round($distance, 2) : '',
            ]
        );
    }

    /**
     * Notify client: Courier picked up package
     */
    public function notifyOrderPickedUp(Order $order): bool
    {
        return $this->sendToUser(
            $order->client,
            '📦 Colis récupéré',
            "Votre colis est en route vers {$order->dropoff_address}",
            [
                'type' => 'order_picked_up',
                'order_id' => $order->id,
            ]
        );
    }

    /**
     * Notify client: Order delivered
     */
    public function notifyOrderDelivered(Order $order): bool
    {
        return $this->sendToUser(
            $order->client,
            '✅ Livraison effectuée',
            "Votre colis a été livré à {$order->dropoff_contact_name}. Merci d'utiliser OUAGA CHAP!",
            [
                'type' => 'order_delivered',
                'order_id' => $order->id,
            ]
        );
    }

    /**
     * Notify courier: Order cancelled
     */
    public function notifyOrderCancelled(Order $order): bool
    {
        if (!$order->courier) {
            return false;
        }

        return $this->sendToUser(
            $order->courier,
            '❌ Course annulée',
            "La course #{$order->order_number} a été annulée par le client.",
            [
                'type' => 'order_cancelled',
                'order_id' => $order->id,
            ]
        );
    }

    // =========================================================================
    // NOTIFICATION TEMPLATES - PAYMENTS
    // =========================================================================

    /**
     * Notify client: Payment received
     */
    public function notifyPaymentReceived(Order $order): bool
    {
        return $this->sendToUser(
            $order->client,
            '💰 Paiement confirmé',
            "Paiement de {$order->total_price} FCFA reçu pour la commande #{$order->order_number}",
            [
                'type' => 'payment_received',
                'order_id' => $order->id,
                'amount' => (string) $order->total_price,
            ]
        );
    }

    /**
     * Notify courier: Earnings credited
     */
    public function notifyCourierEarnings(Order $order): bool
    {
        if (!$order->courier) {
            return false;
        }

        return $this->sendToUser(
            $order->courier,
            '💵 Gain crédité',
            "{$order->courier_earnings} FCFA ajouté à votre portefeuille pour la course #{$order->order_number}",
            [
                'type' => 'earnings_credited',
                'order_id' => $order->id,
                'amount' => (string) $order->courier_earnings,
            ]
        );
    }

    // =========================================================================
    // NOTIFICATION TEMPLATES - COURIER STATUS
    // =========================================================================

    /**
     * Notify client: Courier is arriving
     */
    public function notifyCourierArriving(Order $order, int $minutesAway): bool
    {
        return $this->sendToUser(
            $order->client,
            '🏍️ Coursier en approche',
            "Votre coursier arrive dans environ {$minutesAway} minutes",
            [
                'type' => 'courier_arriving',
                'order_id' => $order->id,
                'eta_minutes' => (string) $minutesAway,
            ]
        );
    }

    // =========================================================================
    // BROADCAST TO ALL COURIERS
    // =========================================================================

    /**
     * Broadcast new order to available couriers using smart matching
     * Priorise les coursiers les mieux adaptés via l'algorithme IA
     */
    public function broadcastToAvailableCouriers(Order $order): array
    {
        $orderDetails = [
            'is_large' => $order->is_large ?? false,
            'is_fragile' => $order->is_fragile ?? false,
            'order_type' => $order->order_type ?? 'standard',
            'weight' => $order->weight ?? 0,
        ];

        // Récupérer les coursiers triés par score IA (limite 20)
        $smartCouriers = $this->courierService->getSmartMatchedCouriers(
            $order->pickup_latitude,
            $order->pickup_longitude,
            $orderDetails,
            radiusKm: 10,
            limit: 20
        );

        // Fallback: si aucun coursier dans le rayon, prendre tous les disponibles
        if ($smartCouriers->isEmpty()) {
            $smartCouriers = User::query()
                ->where('role', 'courier')
                ->where('status', 'active')
                ->where('is_available', true)
                ->whereNotNull('fcm_token')
                ->get();
        }

        $results = ['success' => 0, 'failed' => 0, 'couriers' => []];

        foreach ($smartCouriers as $courier) {
            // Vérifier que le coursier a un token FCM
            if (empty($courier->fcm_token)) {
                continue;
            }

            $sent = $this->notifyNewOrderAvailable($order, $courier);

            if ($sent) {
                $results['success']++;
                $results['couriers'][] = [
                    'id' => $courier->id,
                    'score' => $courier->matching_score ?? null,
                ];
            } else {
                $results['failed']++;
            }
        }

        Log::channel('api')->info("Smart broadcast to couriers", [
            'order_id' => $order->id,
            'order_type' => $order->order_type ?? 'standard',
            'is_urgent' => $order->priority === 'urgent' || $order->priority === 'express',
            'total_candidates' => $smartCouriers->count(),
            'results' => $results,
        ]);

        return $results;
    }
}
