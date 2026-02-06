<?php

namespace App\Services;

use App\Enums\NotificationType;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    public function __construct(
        private PushNotificationService $pushService,
        private SmsService $smsService
    ) {}

    /**
     * Envoyer une notification à un utilisateur
     */
    public function send(
        User $user,
        NotificationType $type,
        array $data = [],
        ?string $customTitle = null,
        ?string $customMessage = null
    ): bool {
        // Vérifier les préférences de l'utilisateur
        if (!$this->shouldNotify($user, $type)) {
            Log::info("Notification {$type->value} désactivée pour l'utilisateur {$user->id}");
            return false;
        }

        $title = $customTitle ?? $type->getTitle();
        $message = $customMessage ?? $type->getDefaultMessage($data);
        $channels = $type->getChannel();

        $success = false;

        foreach ($channels as $channel) {
            match ($channel) {
                'push' => $success = $this->sendPush($user, $title, $message, $data) || $success,
                'sms' => $success = $this->sendSms($user, $message) || $success,
                default => null,
            };
        }

        return $success;
    }

    /**
     * Envoyer une notification push
     */
    private function sendPush(User $user, string $title, string $message, array $data = []): bool
    {
        if (!$user->fcm_token) {
            return false;
        }

        try {
            $this->pushService->sendToUser($user, $title, $message, $data);
            return true;
        } catch (\Exception $e) {
            Log::error("Erreur push notification: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Envoyer un SMS
     */
    private function sendSms(User $user, string $message): bool
    {
        if (!$user->phone) {
            return false;
        }

        try {
            $this->smsService->send($user->phone, $message);
            return true;
        } catch (\Exception $e) {
            Log::error("Erreur SMS: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Vérifier si l'utilisateur souhaite recevoir ce type de notification
     */
    private function shouldNotify(User $user, NotificationType $type): bool
    {
        // Notifications critiques toujours envoyées
        if (!$type->isOptional()) {
            return true;
        }

        $preferences = $user->notification_preferences ?? $this->getDefaultPreferences();
        
        return $preferences[$type->value] ?? true;
    }

    /**
     * Préférences par défaut
     */
    public function getDefaultPreferences(): array
    {
        return [
            // Commandes
            NotificationType::ORDER_CREATED->value => true,
            NotificationType::ORDER_CONFIRMED->value => true,
            NotificationType::ORDER_ASSIGNED->value => true,
            NotificationType::ORDER_PICKED_UP->value => true,
            NotificationType::ORDER_IN_TRANSIT->value => true,
            NotificationType::ORDER_DELIVERED->value => true,
            NotificationType::ORDER_CANCELLED->value => true,

            // Coursiers
            NotificationType::NEW_ORDER_AVAILABLE->value => true,
            NotificationType::ORDER_ACCEPTED->value => true,

            // Paiements
            NotificationType::PAYMENT_RECEIVED->value => true,
            NotificationType::PAYMENT_FAILED->value => true,

            // Portefeuille
            NotificationType::WALLET_CREDITED->value => true,
            NotificationType::WITHDRAWAL_REQUESTED->value => true,
            NotificationType::WITHDRAWAL_APPROVED->value => true,
            NotificationType::WITHDRAWAL_COMPLETED->value => true,
            NotificationType::WITHDRAWAL_REJECTED->value => true,

            // Marketing (désactivé par défaut)
            NotificationType::PROMOTIONAL->value => false,
        ];
    }

    /**
     * Mettre à jour les préférences de notification d'un utilisateur
     */
    public function updatePreferences(User $user, array $preferences): void
    {
        $currentPreferences = $user->notification_preferences ?? $this->getDefaultPreferences();
        
        foreach ($preferences as $key => $value) {
            // Vérifier que le type de notification existe
            $type = NotificationType::tryFrom($key);
            if ($type && $type->isOptional()) {
                $currentPreferences[$key] = (bool) $value;
            }
        }

        $user->update(['notification_preferences' => $currentPreferences]);
    }

    /**
     * Envoyer une notification à plusieurs utilisateurs
     */
    public function sendToMany(
        array $users,
        NotificationType $type,
        array $data = [],
        ?string $customTitle = null,
        ?string $customMessage = null
    ): int {
        $sent = 0;

        foreach ($users as $user) {
            if ($this->send($user, $type, $data, $customTitle, $customMessage)) {
                $sent++;
            }
        }

        return $sent;
    }

    /**
     * Envoyer une notification à tous les coursiers actifs
     */
    public function notifyActiveCouriers(
        NotificationType $type,
        array $data = [],
        ?string $customTitle = null,
        ?string $customMessage = null
    ): int {
        $couriers = User::where('role', 'courier')
            ->where('is_active', true)
            ->where('is_available', true)
            ->get();

        return $this->sendToMany($couriers->all(), $type, $data, $customTitle, $customMessage);
    }
}
