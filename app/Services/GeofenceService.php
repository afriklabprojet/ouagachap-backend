<?php

namespace App\Services;

use App\Models\GeofenceAlert;
use App\Models\Order;
use App\Models\Zone;

class GeofenceService
{
    const PROXIMITY_THRESHOLD = 200; // mètres
    const OUT_OF_BOUNDS_THRESHOLD = 5000; // 5 km hors zone

    /**
     * Vérifier la position du coursier et créer des alertes si nécessaire
     */
    public function checkPosition(Order $order, float $latitude, float $longitude): array
    {
        $alerts = [];

        // Vérifier proximité point de collecte
        if (in_array($order->status->value ?? $order->status, ['assigned', 'picked_up'])) {
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
                $order->delivery_latitude,
                $order->delivery_longitude
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
     * Vérifier si une position est dans une zone active
     */
    public function isInAnyZone(float $latitude, float $longitude): bool
    {
        $zones = Zone::where('is_active', true)->get();

        foreach ($zones as $zone) {
            if ($this->isInZone($latitude, $longitude, $zone)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Vérifier si une position est dans une zone spécifique
     */
    public function isInZone(float $latitude, float $longitude, Zone $zone): bool
    {
        // Calculer la distance au centre de la zone
        $distance = $this->calculateDistance(
            $latitude,
            $longitude,
            $zone->center_latitude ?? $zone->latitude,
            $zone->center_longitude ?? $zone->longitude
        );

        // Rayon par défaut de 10km si non spécifié
        $radius = $zone->radius ?? 10000;

        return $distance <= $radius;
    }

    /**
     * Obtenir le tarif dynamique pour une zone
     */
    public function getDynamicPricing(Zone $zone): float
    {
        if (!$zone->surge_active) {
            return 1.0;
        }

        // Vérifier les heures de pointe si configurées
        if ($zone->surge_schedule) {
            $currentHour = (int) now()->format('H');
            $currentDay = strtolower(now()->format('l'));

            $schedule = $zone->surge_schedule;

            // Vérifier si l'heure actuelle est dans une période de pointe
            foreach ($schedule as $period) {
                if (isset($period['days']) && !in_array($currentDay, $period['days'])) {
                    continue;
                }

                $startHour = $period['start_hour'] ?? 0;
                $endHour = $period['end_hour'] ?? 24;

                if ($currentHour >= $startHour && $currentHour < $endHour) {
                    return $zone->surge_multiplier;
                }
            }

            return 1.0;
        }

        return $zone->surge_multiplier;
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
     * Calculer la distance entre deux points (Haversine)
     */
    public function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000; // mètres

        $lat1Rad = deg2rad($lat1);
        $lat2Rad = deg2rad($lat2);
        $deltaLat = deg2rad($lat2 - $lat1);
        $deltaLng = deg2rad($lng2 - $lng1);

        $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
            cos($lat1Rad) * cos($lat2Rad) *
            sin($deltaLng / 2) * sin($deltaLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
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
