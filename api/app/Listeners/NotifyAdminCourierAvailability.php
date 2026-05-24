<?php

namespace App\Listeners;

use App\Events\CourierWentOnline;
use App\Models\User;
use App\Services\PushNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Notifie les admins quand le nombre de coursiers disponibles passe sous le seuil.
 */
class NotifyAdminCourierAvailability implements ShouldQueue
{
    private const CRITICAL_COURIER_THRESHOLD = 2;

    public function __construct(
        private PushNotificationService $pushService
    ) {}

    public function handle(CourierWentOnline $event): void
    {
        // Ne vérifier que quand un coursier passe hors ligne
        if ($event->status !== 'offline') {
            return;
        }

        try {
            $availableCount = User::couriers()
                ->active()
                ->available()
                ->count();

            if ($availableCount <= self::CRITICAL_COURIER_THRESHOLD) {
                $admins = User::admins()->active()->whereNotNull('fcm_token')->get();

                foreach ($admins as $admin) {
                    $this->pushService->sendToUser(
                        $admin,
                        '⚠️ Alerte coursiers',
                        "Seulement {$availableCount} coursier(s) disponible(s) en ce moment.",
                        [
                            'type' => 'admin_low_couriers',
                            'available_count' => (string) $availableCount,
                        ]
                    );
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed to send courier availability alert', [
                'courier_id' => $event->courier->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
