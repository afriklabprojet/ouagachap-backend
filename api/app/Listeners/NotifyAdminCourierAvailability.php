<?php

namespace App\Listeners;

use App\Enums\UserRole;
use App\Events\CourierWentOnline;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class NotifyAdminCourierAvailability implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(CourierWentOnline $event): void
    {
        $courier = $event->courier;
        $action = $event->action;
        
        $courierName = $courier->name ?? $courier->phone;
        $vehicleEmoji = match($courier->vehicle_type) {
            'moto' => '🏍️',
            'velo' => '🚲',
            'voiture' => '🚗',
            default => '🛵',
        };
        
        if ($action === 'online') {
            $title = 'Coursier en ligne';
            $message = "{$vehicleEmoji} {$courierName} est maintenant disponible pour les livraisons";
            $type = 'courier_online';
        } else {
            $title = 'Coursier hors ligne';
            $message = "{$vehicleEmoji} {$courierName} n'est plus disponible";
            $type = 'courier_offline';
        }

        // Créer une notification pour tous les admins
        $admins = User::where('role', UserRole::ADMIN)->get();
        
        foreach ($admins as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'data' => [
                    'courier_id' => $courier->id,
                    'courier_name' => $courierName,
                    'courier_phone' => $courier->phone,
                    'vehicle_type' => $courier->vehicle_type,
                    'action' => $action,
                    'latitude' => $courier->current_latitude,
                    'longitude' => $courier->current_longitude,
                ],
            ]);
        }

        // Log pour le suivi
        Log::info("Coursier {$action}", [
            'courier_id' => $courier->id,
            'courier_name' => $courierName,
            'notified_admins' => $admins->count(),
        ]);
    }
}
