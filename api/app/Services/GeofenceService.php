<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Geofence;
use App\Models\GeofenceAlert;
use App\Models\Order;
use App\Models\Zone;
use App\Traits\CalculatesDistance;

class GeofenceService
{
    use CalculatesDistance;

    const PROXIMITY_THRESHOLD = 200; // mètres
    const OUT_OF_BOUNDS_THRESHOLD = 5000; // 5 km hors zone

    /**
     * Vérifier la position du coursier et créer des alertes si nécessaire
     */
    public function checkPosition(Order $order, float $latitude, float $longitude): array
    {
        $alerts = [];

        // Vérifier proximité point de collecte
        $activeStatusValues = array_map(fn($s) => $s->value, OrderStatus::activeStatuses());
        if (in_array($order->status->value ?? $order->status, $activeStatusValues)) {
            $distanceToPickup = $this->calculateDistance(
                $latitude,
                $longitude,
                $order->pickup_latitude,
                $order->pickup_longitude
            );

            if ($distanceToPickup <= self::PROXIMITY_THRESHOLD) {
                // Vérifier si une alerte existe déjà récemment
                $existingAlert = GeofenceAlert::where('order_id', $order->id)
                    ->where('type', GeofenceAlert::TYPE_PROXIMITY_PICKUP)
                    ->where('created_at', '>', now()->subMinutes(5))
                    ->exists();

                if (!$existingAlert) {
                    $alerts[] = GeofenceAlert::createProximityPickup(
                        $order,
                        $latitude,
                        $longitude,
                        $distanceToPickup
                    );
                }
            }
        }

        // Vérifier proximité point de livraison
        if (in_array($order->status->value ?? $order->status, ['picked_up', 'in_transit'])) {
            $distanceToDelivery = $this->calculateDistance(
                $latitude,
                $longitude,
                $order->dropoff_latitude,
                $order->dropoff_longitude
            );

            if ($distanceToDelivery <= self::PROXIMITY_THRESHOLD) {
                $existingAlert = GeofenceAlert::where('order_id', $order->id)
                    ->where('type', GeofenceAlert::TYPE_PROXIMITY_DELIVERY)
                    ->where('created_at', '>', now()->subMinutes(5))
                    ->exists();

                if (!$existingAlert) {
                    $alerts[] = GeofenceAlert::createProximityDelivery(
                        $order,
                        $latitude,
                        $longitude,
                        $distanceToDelivery
                    );
                }
            }
        }

        // Vérifier si le coursier est hors zone
        if (!$this->isInAnyZone($latitude, $longitude)) {
            $existingAlert = GeofenceAlert::where('order_id', $order->id)
                ->where('type', GeofenceAlert::TYPE_OUT_OF_BOUNDS)
                ->where('created_at', '>', now()->subMinutes(30))
                ->exists();

            if (!$existingAlert) {
                $alerts[] = GeofenceAlert::createOutOfBounds($order, $latitude, $longitude);
            }
        }

        return $alerts;
    }

    /**
     * Vérifier si une position est dans une zone active (via Geofence polygons)
     */
    public function isInAnyZone(float $latitude, float $longitude): bool
    {
        $geofences = Geofence::where('is_active', true)
            ->where('type', 'allowed')
            ->get();

        foreach ($geofences as $geofence) {
            if ($geofence->containsPoint($latitude, $longitude)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Vérifier si une position est dans une geofence spécifique
     */
    public function isInZone(float $latitude, float $longitude, Zone $zone): bool
    {
        // Chercher la geofence correspondante à cette zone par le nom
        $geofence = Geofence::where('is_active', true)
            ->where('name', $zone->name)
            ->first();

        if ($geofence) {
            return $geofence->containsPoint($latitude, $longitude);
        }

        // Fallback : considérer tout Ouagadougou comme zone valide (rayon ~15km du centre)
        $centerLat = 12.3714;
        $centerLng = -1.5197;
        $distance = $this->calculateDistance($latitude, $longitude, $centerLat, $centerLng);

        return $distance <= 15000; // 15 km
    }

    /**
     * Obtenir le tarif dynamique pour une zone
     * Utilise les geofences de type 'surge' pour le multiplicateur
     */
    public function getDynamicPricing(Zone $zone): float
    {
        // Chercher une geofence surge active correspondant à cette zone
        $surgeGeofence = Geofence::where('is_active', true)
            ->where('type', 'surge')
            ->where('name', 'like', "%{$zone->name}%")
            ->first();

        if (!$surgeGeofence || !$surgeGeofence->surge_multiplier || $surgeGeofence->surge_multiplier <= 1.0) {
            return 1.0;
        }

        return (float) $surgeGeofence->surge_multiplier;
    }

    /**
     * Calculer le frais de livraison avec tarification dynamique
     */
    public function calculateDeliveryFee(Zone $pickupZone, Zone $deliveryZone, float $distance): float
    {
        $baseFee = $deliveryZone->base_price ?? 500;

        // Appliquer le multiplicateur de la zone de livraison
        $surgeMultiplier = $this->getDynamicPricing($deliveryZone);

        // Frais supplémentaire par km au-delà de 5km
        $distanceKm = $distance / 1000;
        $extraDistanceFee = max(0, ($distanceKm - 5) * 100);

        return ($baseFee + $extraDistanceFee) * $surgeMultiplier;
    }

    /**
     * Calculer la distance entre deux points (en mètres, pour compatibilité geofence)
     */
    public function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        return $this->calculateDistanceMeters($lat1, $lng1, $lat2, $lng2);
    }

    /**
     * Obtenir les alertes non lues pour un coursier
     */
    public function getUnreadAlerts(int $courierId, ?int $orderId = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = GeofenceAlert::forCourier($courierId)->unread()->latest();

        if ($orderId) {
            $query->where('order_id', $orderId);
        }

        return $query->get();
    }

    /**
     * Marquer les alertes comme lues
     */
    public function markAlertsAsRead(array $alertIds): int
    {
        return GeofenceAlert::whereIn('id', $alertIds)->update(['is_read' => true]);
    }
}
