<?php

namespace App\Services;

use App\DTOs\PushNotificationDTO;
use App\Enums\NotificationChannel;
use App\Models\User;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Messaging\AndroidConfig;
use Kreait\Firebase\Messaging\ApnsConfig;
use Kreait\Firebase\Messaging\MulticastSendReport;
use Kreait\Firebase\Exception\MessagingException;

/**
 * Service amélioré pour les notifications push Firebase
 *
 * Features:
 * - Canaux de notification par type
 * - Retry automatique avec backoff exponentiel
 * - Envoi par batch (multicast)
 * - Templates de notifications
 * - Analytics et logging
 * - Gestion des tokens invalides
 */
class EnhancedPushNotificationService
{
    protected ?Messaging $messaging;

    private CircuitBreaker $cb;

    /** Nombre max de tentatives d'envoi */
    private const MAX_RETRIES = 3;

    /** Taille max d'un batch multicast */
    private const BATCH_SIZE = 500;

    /** Durée de vie du message (4 semaines max) */
    private const TTL_SECONDS = 2419200;

    public function __construct()
    {
        $this->cb = new CircuitBreaker('firebase_fcm', threshold: 10, cooldown: 120);

        try {
            $this->messaging = app('firebase.messaging');
        } catch (\Exception $e) {
            Log::warning('Firebase not configured: ' . $e->getMessage());
            $this->messaging = null;
        }
    }

    // =========================================================================
    // MÉTHODES PRINCIPALES D'ENVOI
    // =========================================================================

    /**
     * Envoyer une notification à un utilisateur
     */
    public function sendToUser(User $user, PushNotificationDTO $notification): bool
    {
        if (!$user->fcm_token) {
            Log::info("User {$user->id} has no FCM token");
            return false;
        }

        return $this->sendWithRetry($user->fcm_token, $notification);
    }

    /**
     * Envoyer à plusieurs utilisateurs (optimisé avec multicast)
     */
    public function sendToUsers(array $users, PushNotificationDTO $notification): array
    {
        $tokens = collect($users)
            ->filter(fn($user) => $user->fcm_token !== null)
            ->pluck('fcm_token')
            ->unique()
            ->values()
            ->toArray();

        if (empty($tokens)) {
            return ['success' => 0, 'failed' => 0, 'total' => count($users)];
        }

        return $this->sendMulticast($tokens, $notification);
    }

    /**
     * Envoyer à un topic (tous les coursiers, promotions, etc.)
     */
    public function sendToTopic(string $topic, PushNotificationDTO $notification): bool
    {
        if (!$this->messaging) {
            $this->logNotification('topic', $topic, $notification);
            return true;
        }

        if ($this->cb->isOpen()) {
            Log::warning('FCM circuit ouvert — topic ignoré', ['topic' => $topic]);
            return false;
        }

        try {
            $message = $this->buildTopicMessage($topic, $notification);
            $this->messaging->send($message);

            $this->cb->recordSuccess();
            $this->logSuccess('topic', $topic, $notification);
            return true;
        } catch (MessagingException $e) {
            $this->cb->recordFailure();
            $this->logError('topic', $topic, $e);
            return false;
        }
    }

    // =========================================================================
    // NOTIFICATIONS PAR TYPE - COMMANDES
    // =========================================================================

    /**
     * Notifier le client: commande créée
     */
    public function notifyOrderCreated(Order $order): bool
    {
        $notification = PushNotificationDTO::forOrderCreated(
            $order->order_number
        );

        $notification = new PushNotificationDTO(
            title: $notification->title,
            body: $notification->body,
            channel: $notification->channel,
            data: array_merge($notification->data, ['order_id' => $order->id]),
        );

        return $this->sendToUser($order->client, $notification);
    }

    /**
     * Notifier le client: coursier assigné
     */
    public function notifyOrderAssigned(Order $order): bool
    {
        if (!$order->courier) {
            return false;
        }

        $notification = PushNotificationDTO::forOrderAssigned(
            orderId: $order->id,
            courierName: $order->courier->name,
            courierPhoto: $order->courier->avatar_url,
        );

        return $this->sendToUser($order->client, $notification);
    }

    /**
     * Notifier les coursiers: nouvelle commande disponible
     */
    public function notifyNewOrderAvailable(Order $order, User $courier): bool
    {
        $notification = PushNotificationDTO::forNewOrderAvailable(
            orderId: $order->id,
            pickupAddress: $this->shortenAddress($order->pickup_address),
            deliveryAddress: $this->shortenAddress($order->dropoff_address),
            distance: $order->distance_km ?? 0,
            earnings: $order->courier_earnings ?? 0,
        );

        return $this->sendToUser($courier, $notification);
    }

    /**
     * Broadcast nouvelle commande à tous les coursiers disponibles
     */
    public function broadcastNewOrder(Order $order): array
    {
        $couriers = User::query()
            ->where('role', 'courier')
            ->where('status', 'active')
            ->where('is_available', true)
            ->whereNotNull('fcm_token')
            ->get();

        if ($couriers->isEmpty()) {
            return ['success' => 0, 'failed' => 0, 'total' => 0];
        }

        $notification = PushNotificationDTO::forNewOrderAvailable(
            orderId: $order->id,
            pickupAddress: $this->shortenAddress($order->pickup_address),
            deliveryAddress: $this->shortenAddress($order->dropoff_address),
            distance: $order->distance_km ?? 0,
            earnings: $order->courier_earnings ?? 0,
        );

        return $this->sendToUsers($couriers->all(), $notification);
    }

    /**
     * Notifier le client: colis récupéré
     */
    public function notifyOrderPickedUp(Order $order): bool
    {
        $notification = PushNotificationDTO::forOrderPickedUp(
            orderId: $order->id,
            deliveryAddress: $this->shortenAddress($order->dropoff_address),
        );

        return $this->sendToUser($order->client, $notification);
    }

    /**
     * Notifier le client: livraison effectuée
     */
    public function notifyOrderDelivered(Order $order): bool
    {
        $notification = PushNotificationDTO::forOrderDelivered(
            orderId: $order->id,
            recipientName: $order->recipient_name ?? 'le destinataire',
            totalAmount: $order->total_price ?? 0,
        );

        return $this->sendToUser($order->client, $notification);
    }

    /**
     * Notifier le coursier: commande annulée
     */
    public function notifyOrderCancelled(Order $order): bool
    {
        if (!$order->courier) {
            return false;
        }

        $notification = PushNotificationDTO::urgent(
            title: 'Course annulée',
            body: "La course #{$order->order_number} a été annulée.",
            data: [
                'order_id' => $order->id,
                'type' => 'order_cancelled',
            ],
        );

        return $this->sendToUser($order->courier, $notification);
    }

    // =========================================================================
    // NOTIFICATIONS PAR TYPE - PAIEMENTS
    // =========================================================================

    /**
     * Notifier le client: paiement reçu
     */
    public function notifyPaymentReceived(Order $order, string $paymentMethod = 'Mobile Money'): bool
    {
        $notification = PushNotificationDTO::forPaymentReceived(
            orderId: $order->id,
            amount: $order->total_price ?? 0,
            paymentMethod: $paymentMethod,
        );

        return $this->sendToUser($order->client, $notification);
    }

    /**
     * Notifier le coursier: gains crédités
     */
    public function notifyCourierEarnings(Order $order): bool
    {
        if (!$order->courier) {
            return false;
        }

        $newBalance = $order->courier->wallet_balance ?? 0;

        $notification = PushNotificationDTO::forEarningsCredited(
            orderId: $order->id,
            orderNumber: $order->order_number,
            amount: $order->courier_earnings ?? 0,
            newBalance: $newBalance,
        );

        return $this->sendToUser($order->courier, $notification);
    }

    // =========================================================================
    // NOTIFICATIONS PAR TYPE - CHAT
    // =========================================================================

    /**
     * Notifier d'un nouveau message de chat
     */
    public function notifyChatMessage(
        User $recipient,
        string $senderName,
        string $message,
        string $orderId,
    ): bool {
        $preview = strlen($message) > 50
            ? substr($message, 0, 47) . '...'
            : $message;

        $notification = PushNotificationDTO::forNewChatMessage(
            orderId: $orderId,
            senderName: $senderName,
            messagePreview: $preview,
        );

        return $this->sendToUser($recipient, $notification);
    }

    // =========================================================================
    // NOTIFICATIONS PAR TYPE - LOCALISATION
    // =========================================================================

    /**
     * Notifier le client: coursier en approche
     */
    public function notifyCourierArriving(Order $order, int $minutesAway): bool
    {
        // Éviter le spam - max 1 notification toutes les 5 minutes
        $cacheKey = "courier_arriving_{$order->id}";
        if (Cache::has($cacheKey)) {
            return true;
        }
        Cache::put($cacheKey, true, now()->addMinutes(5));

        $notification = PushNotificationDTO::forCourierArriving(
            orderId: $order->id,
            minutesAway: $minutesAway,
        );

        return $this->sendToUser($order->client, $notification);
    }

    // =========================================================================
    // NOTIFICATIONS PAR TYPE - ÉVALUATIONS
    // =========================================================================

    /**
     * Notifier d'une nouvelle évaluation
     */
    public function notifyNewRating(
        User $user,
        int $rating,
        ?string $comment,
    ): bool {
        $notification = PushNotificationDTO::forNewRating(
            rating: $rating,
            comment: $comment,
            newAverage: $user->average_rating ?? $rating,
        );

        return $this->sendToUser($user, $notification);
    }

    // =========================================================================
    // MÉTHODES INTERNES
    // =========================================================================

    /**
     * Envoyer avec retry automatique et backoff exponentiel
     */
    protected function sendWithRetry(string $token, PushNotificationDTO $notification, int $attempt = 1): bool
    {
        if (!$this->messaging) {
            $this->logNotification('token', substr($token, 0, 20), $notification);
            return true;
        }

        if ($this->cb->isOpen()) {
            Log::warning('FCM circuit ouvert — notification ignorée', ['token_prefix' => substr($token, 0, 8)]);
            return false;
        }

        try {
            $message = $this->buildMessage($token, $notification);
            $this->messaging->send($message);

            $this->cb->recordSuccess();
            $this->logSuccess('token', substr($token, 0, 20), $notification);
            return true;
        } catch (MessagingException $e) {
            // Token invalide - supprimer (ne compte pas comme échec circuit)
            if ($this->isInvalidToken($e)) {
                $this->clearInvalidToken($token);
                return false;
            }

            // Erreur temporaire - retry
            if ($attempt < self::MAX_RETRIES && $this->isRetryable($e)) {
                // @codeCoverageIgnoreStart
                $delay = pow(2, $attempt) * 100; // 200ms, 400ms, 800ms
                usleep($delay * 1000);
                return $this->sendWithRetry($token, $notification, $attempt + 1);
                // @codeCoverageIgnoreEnd
            }

            $this->cb->recordFailure();
            $this->logError('token', substr($token, 0, 20), $e);
            return false;
        }
    }

    /**
     * Envoyer à plusieurs tokens (multicast)
     */
    protected function sendMulticast(array $tokens, PushNotificationDTO $notification): array
    {
        if (!$this->messaging) {
            return [
                'success' => count($tokens),
                'failed' => 0,
                'total' => count($tokens),
            ];
        }

        $results = ['success' => 0, 'failed' => 0, 'total' => count($tokens)];
        $invalidTokens = [];

        // Envoyer par batches de 500 (limite FCM)
        foreach (array_chunk($tokens, self::BATCH_SIZE) as $batch) {
            try {
                $message = $this->buildMulticastMessage($notification);
                /** @var MulticastSendReport $report */
                $report = $this->messaging->sendMulticast($message, $batch);

                $results['success'] += $report->successes()->count();
                $results['failed'] += $report->failures()->count();

                // Collecter les tokens invalides
                foreach ($report->failures()->getItems() as $failure) {
                    if ($this->isInvalidTokenError($failure->error()?->getMessage())) {
                        $invalidTokens[] = $failure->target()->value();
                    }
                }
            } catch (MessagingException $e) {
                $results['failed'] += count($batch);
                $this->logError('multicast', 'batch', $e);
            }
        }

        // Nettoyer les tokens invalides
        if (!empty($invalidTokens)) {
            $this->clearInvalidTokens($invalidTokens);
        }

        Log::channel('api')->info("Multicast notification sent", [
            'results' => $results,
            'channel' => $notification->channel->value,
        ]);

        return $results;
    }

    /**
     * Construire le message FCM pour un token.
     *
     * Pour les canaux isDataOnly() (NEW_ORDER, URGENT) : message data-only sans
     * payload notification. Cela force Android à appeler onBackgroundMessage de
     * Flutter, qui affiche une notification fullScreenIntent réveillant l'écran
     * même en veille. Avec un payload notification, le handler background n'est
     * jamais appelé et l'écran reste éteint.
     */
    protected function buildMessage(string $token, PushNotificationDTO $notification): CloudMessage
    {
        $base = CloudMessage::new()->toToken($token)
            ->withData($this->buildDataPayload($notification))
            ->withAndroidConfig($this->buildAndroidConfig($notification))
            ->withApnsConfig($this->buildApnsConfig($notification));

        if (!$notification->channel->isDataOnly()) {
            return $base->withNotification(Notification::create(
                $notification->title,
                $notification->body,
                $notification->imageUrl,
            ));
        }

        return $base;
    }

    /**
     * Construire le message FCM pour multicast
     */
    protected function buildMulticastMessage(PushNotificationDTO $notification): CloudMessage
    {
        $base = CloudMessage::new()
            ->withData($this->buildDataPayload($notification))
            ->withAndroidConfig($this->buildAndroidConfig($notification))
            ->withApnsConfig($this->buildApnsConfig($notification));

        if (!$notification->channel->isDataOnly()) {
            return $base->withNotification(Notification::create(
                $notification->title,
                $notification->body,
                $notification->imageUrl,
            ));
        }

        return $base;
    }

    /**
     * Construire le message pour un topic
     */
    protected function buildTopicMessage(string $topic, PushNotificationDTO $notification): CloudMessage
    {
        $base = CloudMessage::new()->toTopic($topic)
            ->withData($this->buildDataPayload($notification))
            ->withAndroidConfig($this->buildAndroidConfig($notification))
            ->withApnsConfig($this->buildApnsConfig($notification));

        if (!$notification->channel->isDataOnly()) {
            return $base->withNotification(Notification::create(
                $notification->title,
                $notification->body,
                $notification->imageUrl,
            ));
        }

        return $base;
    }

    /**
     * Construit le payload data FCM (toutes valeurs en string, obligatoire FCM).
     * Pour les canaux data-only, ajoute notification_title/body afin que le
     * handler Flutter background puisse les afficher dans sa notification locale.
     */
    protected function buildDataPayload(PushNotificationDTO $notification): array
    {
        $data = $this->sanitizeData($notification->data);
        $data['channel'] = $notification->channel->value;

        if ($notification->channel->isDataOnly()) {
            $data['notification_title'] = $notification->title;
            $data['notification_body']  = $notification->body;
        }

        return $data;
    }

    /**
     * Configuration Android spécifique au canal.
     *
     * Pour les canaux data-only, on n'inclut PAS de section 'notification'.
     * Si on la laissait, FCM l'afficherait dans le barre de statut et
     * n'appellerait pas le handler background Flutter.
     */
    protected function buildAndroidConfig(PushNotificationDTO $notification): AndroidConfig
    {
        $channel = $notification->channel;

        $config = [
            'priority' => $channel->androidPriority(), // 'high' bypass le mode Doze
            'ttl'      => self::TTL_SECONDS . 's',
        ];

        if (!$channel->isDataOnly()) {
            $config['notification'] = [
                'channel_id'           => $channel->value,
                'sound'                => $channel->sound() ?? 'default',
                'click_action'         => 'FLUTTER_NOTIFICATION_CLICK',
                'notification_priority' => $channel->androidPriority() === 'high'
                    ? 'PRIORITY_MAX'
                    : 'PRIORITY_DEFAULT',
            ];
        }

        return AndroidConfig::fromArray($config);
    }

    /**
     * Configuration iOS spécifique au canal.
     *
     * Pour les canaux data-only : APNS doit inclure l'alert dans son propre
     * payload pour que iOS affiche la notification (le handler background
     * Flutter sur iOS est trop limité pour être fiable en veille profonde).
     * content-available:1 réveille l'app en background si possible.
     */
    protected function buildApnsConfig(PushNotificationDTO $notification): ApnsConfig
    {
        $channel = $notification->channel;

        if ($channel->isDataOnly()) {
            return ApnsConfig::fromArray([
                'headers' => [
                    'apns-priority'   => '10',
                    'apns-push-type'  => 'alert',
                ],
                'payload' => [
                    'aps' => [
                        'alert' => [
                            'title' => $notification->title,
                            'body'  => $notification->body,
                        ],
                        'sound'             => 'default',
                        'badge'             => $notification->badgeCount ?? 1,
                        'content-available' => 1, // réveille l'app en background iOS
                        'mutable-content'   => 1,
                        'interruption-level' => 'time-sensitive',
                    ],
                ],
            ]);
        }

        $aps = [
            'sound'          => $channel->sound() ?? 'default',
            'badge'          => $notification->badgeCount ?? 1,
            'mutable-content' => 1,
        ];

        if (!empty($notification->actions)) {
            $aps['category'] = $channel->value;
        }

        if ($channel->wakeScreen()) {
            $aps['interruption-level'] = 'time-sensitive';
        }

        return ApnsConfig::fromArray([
            'headers' => [
                'apns-priority'  => $channel->iosPriority(),
                'apns-push-type' => $notification->silent ? 'background' : 'alert',
            ],
            'payload' => [
                'aps' => $aps,
            ],
        ]);
    }

    /**
     * Sanitizer les données (FCM requiert des strings)
     */
    protected function sanitizeData(array $data): array
    {
        return array_map(fn($value) => is_string($value) ? $value : (string) $value, $data);
    }

    /**
     * Raccourcir une adresse pour l'affichage
     */
    protected function shortenAddress(string $address, int $maxLength = 40): string
    {
        if (strlen($address) <= $maxLength) {
            return $address;
        }
        return substr($address, 0, $maxLength - 3) . '...';
    }

    /**
     * Vérifier si l'erreur est due à un token invalide
     */
    protected function isInvalidToken(MessagingException $e): bool
    {
        $message = $e->getMessage();
        return str_contains($message, 'not a valid FCM registration token')
            || str_contains($message, 'Requested entity was not found')
            || str_contains($message, 'UNREGISTERED');
    }

    /**
     * Vérifier si l'erreur est retryable
     */
    protected function isRetryable(MessagingException $e): bool
    {
        $message = $e->getMessage();
        return str_contains($message, 'UNAVAILABLE')
            || str_contains($message, 'INTERNAL')
            || str_contains($message, 'rate limit');
    }

    /**
     * Vérifier si le message d'erreur indique un token invalide
     */
    protected function isInvalidTokenError(?string $message): bool
    {
        if (!$message) {
            return false;
        }
        return str_contains($message, 'not a valid FCM registration token')
            || str_contains($message, 'UNREGISTERED');
    }

    /**
     * Supprimer un token invalide
     */
    protected function clearInvalidToken(string $token): void
    {
        User::where('fcm_token', $token)->update([
            'fcm_token' => null,
            'fcm_token_updated_at' => null,
        ]);
        Log::info("Invalid FCM token cleared (hash: " . substr(hash('sha256', $token), 0, 8) . ")");
    }

    /**
     * Supprimer plusieurs tokens invalides
     */
    protected function clearInvalidTokens(array $tokens): void
    {
        User::whereIn('fcm_token', $tokens)->update([
            'fcm_token' => null,
            'fcm_token_updated_at' => null,
        ]);
        Log::info("Cleared " . count($tokens) . " invalid FCM tokens");
    }

    /**
     * Logger une notification (mode dev)
     */
    protected function logNotification(string $targetType, string $target, PushNotificationDTO $notification): void
    {
        Log::info("Push notification (Firebase disabled)", [
            'target_type' => $targetType,
            'target' => $target,
            'title' => $notification->title,
            'channel' => $notification->channel->value,
        ]);
    }

    /**
     * Logger un succès
     */
    protected function logSuccess(string $targetType, string $target, PushNotificationDTO $notification): void
    {
        Log::channel('api')->info("Push sent", [
            'target_type' => $targetType,
            'target' => $target,
            'channel' => $notification->channel->value,
            'title' => $notification->title,
        ]);
    }

    /**
     * Logger une erreur
     */
    protected function logError(string $targetType, string $target, MessagingException $e): void
    {
        Log::error("FCM error", [
            'target_type' => $targetType,
            'target' => $target,
            'error' => $e->getMessage(),
        ]);
    }
}
