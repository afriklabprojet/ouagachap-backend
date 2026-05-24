<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use App\Traits\CalculatesDistance;

/**
 * DelayPredictorService
 *
 * Prédit le temps d'arrivée (ETA) des livraisons à Ouagadougou.
 * Tient compte de la distance, du trafic, de la météo et des heures de pointe.
 */
final class DelayPredictorService
{
    use CalculatesDistance;

    // Vitesse moyenne d'une moto en livraison à Ouagadougou
    private const BASE_SPEED_KMH = 25.0;

    // Vitesse minimale (conditions dégradées)
    private const MIN_SPEED_KMH = 8.0;

    // Delai de préparation avant départ (minutes)
    private const PICKUP_BUFFER_MINUTES = 3;

    // Heures de pointe à Ouagadougou (heure locale)
    private const PEAK_HOURS = [
        ['start' => 7,  'end' => 9],   // Matin
        ['start' => 12, 'end' => 14],  // Midi
        ['start' => 17, 'end' => 20],  // Soir
    ];

    // Multiplicateurs de délai selon le niveau de trafic
    private const TRAFFIC_MULTIPLIERS = [
        'severe'   => 2.8,
        'high'     => 2.0,
        'moderate' => 1.5,
        'low'      => 1.1,
        'clear'    => 1.0,
    ];

    public function __construct(
        protected WeatherService         $weatherService,
        protected TrafficAnalysisService $trafficAnalysisService,
    ) {}

    /**
     * Prédit l'ETA pour une commande avec un coursier assigné ou le plus proche.
     *
     * @return array{
     *   minutes: int,
     *   optimistic: int,
     *   pessimistic: int,
     *   confidence: float,
     *   breakdown: array,
     *   factors: array
     * }
     */
    public function predictETA(Order $order, ?User $courier = null): array
    {
        // 1. Vérifier la présence des coordonnées GPS
        if (! $order->pickup_latitude || ! $order->dropoff_latitude) {
            return $this->defaultETA('Coordonnées GPS manquantes');
        }

        // 2. Calculer les distances
        $courierToPickup = $this->getCourierToPickupDistance($order, $courier);
        $pickupToDelivery = $this->calculateDistanceKm(
            (float) $order->pickup_latitude,
            (float) $order->pickup_longitude,
            (float) $order->dropoff_latitude,
            (float) $order->dropoff_longitude
        );
        $totalDistance = $courierToPickup + $pickupToDelivery;

        // 3. Score météo (0-100, plus bas = conditions plus difficiles)
        $weatherScore = $this->weatherService->getDeliveryScore(
            (float) $order->pickup_latitude,
            (float) $order->pickup_longitude
        );

        // 4. Score trafic sur la route complète
        $trafficScore = $this->getRouteTrafficScore($order);

        // 5. Multiplicateur heure de pointe
        $peakMultiplier = $this->getPeakHourMultiplier();

        // 6. Multiplicateur météo (mauvais temps = plus lent)
        $weatherMultiplier = $this->getWeatherMultiplier($weatherScore);

        // 7. Multiplicateur trafic
        $trafficMultiplier = $this->getTrafficMultiplier($trafficScore);

        // 8. Calculer ETA central
        $effectiveSpeed = self::BASE_SPEED_KMH
            / ($peakMultiplier * $weatherMultiplier * $trafficMultiplier);

        $effectiveSpeed = max(self::MIN_SPEED_KMH, $effectiveSpeed);

        $travelMinutes = ($totalDistance / $effectiveSpeed) * 60;
        $totalMinutes  = (int) ceil($travelMinutes + self::PICKUP_BUFFER_MINUTES);

        // 9. Fourchette optimiste / pessimiste
        $optimistic  = max(5, (int) floor($totalMinutes * 0.80));
        $pessimistic = (int) ceil($totalMinutes * 1.35);

        // 10. Niveau de confiance (0-1) : meilleur quand trafic + météo sont bons
        $confidence = round(
            ($weatherScore / 100 * 0.4) + ($trafficScore / 100 * 0.6),
            2
        );

        return [
            'minutes'    => $totalMinutes,
            'optimistic' => $optimistic,
            'pessimistic' => $pessimistic,
            'confidence' => $confidence,
            'breakdown'  => [
                'courier_to_pickup_km'  => round($courierToPickup, 2),
                'pickup_to_delivery_km' => round($pickupToDelivery, 2),
                'total_distance_km'     => round($totalDistance, 2),
                'effective_speed_kmh'   => round($effectiveSpeed, 1),
                'travel_minutes'        => round($travelMinutes, 1),
                'buffer_minutes'        => self::PICKUP_BUFFER_MINUTES,
            ],
            'factors'    => [
                'weather_score'       => $weatherScore,
                'weather_multiplier'  => round($weatherMultiplier, 2),
                'traffic_score'       => $trafficScore,
                'traffic_multiplier'  => round($trafficMultiplier, 2),
                'peak_hour'           => $peakMultiplier > 1.0,
                'peak_multiplier'     => round($peakMultiplier, 2),
            ],
        ];
    }

    /**
     * Retourne si on est actuellement en heure de pointe.
     */
    public function isCurrentlyPeakHour(): bool
    {
        $hour = (int) now()->format('H');
        foreach (self::PEAK_HOURS as $peak) {
            if ($hour >= $peak['start'] && $hour < $peak['end']) {
                return true;
            }
        }
        return false;
    }

    /**
     * Retourne l'heure de pointe prochaine (en minutes) ou null si aucune.
     */
    public function minutesToNextPeakHour(): ?int
    {
        $now = now();
        $currentMinutes = (int) $now->format('H') * 60 + (int) $now->format('i');

        foreach (self::PEAK_HOURS as $peak) {
            $peakStartMinutes = $peak['start'] * 60;
            if ($peakStartMinutes > $currentMinutes) {
                return $peakStartMinutes - $currentMinutes;
            }
        }

        // La prochaine heure de pointe est demain matin
        $tomorrowFirst = self::PEAK_HOURS[0]['start'] * 60;
        $minutesInDay  = 24 * 60;
        return $minutesInDay - $currentMinutes + $tomorrowFirst;
    }

    // ─────────────────────────────────────────────────────────────
    // Helpers privés
    // ─────────────────────────────────────────────────────────────

    private function getCourierToPickupDistance(Order $order, ?User $courier): float
    {
        if (
            $courier &&
            $courier->current_latitude  !== null &&
            $courier->current_longitude !== null
        ) {
            return $this->calculateDistanceKm(
                (float) $courier->current_latitude,
                (float) $courier->current_longitude,
                (float) $order->pickup_latitude,
                (float) $order->pickup_longitude
            );
        }

        // Pas de coursier fourni : estimation conservatrice (2 km)
        return 2.0;
    }

    private function getRouteTrafficScore(Order $order): float
    {
        // Moyenne des scores trafic aux 3 points clés (pickup, mi-trajet, delivery)
        $midLat = ((float) $order->pickup_latitude  + (float) $order->dropoff_latitude)  / 2;
        $midLng = ((float) $order->pickup_longitude + (float) $order->dropoff_longitude) / 2;

        $scores = [
            $this->trafficAnalysisService->getTrafficImpactScore(
                (float) $order->pickup_latitude,
                (float) $order->pickup_longitude
            ),
            $this->trafficAnalysisService->getTrafficImpactScore($midLat, $midLng),
            $this->trafficAnalysisService->getTrafficImpactScore(
                (float) $order->dropoff_latitude,
                (float) $order->dropoff_longitude
            ),
        ];

        return round(array_sum($scores) / count($scores), 1);
    }

    private function getPeakHourMultiplier(): float
    {
        $hour = (int) now()->format('H');

        // Pic fort matin/soir → 1.4x ; pic midi → 1.25x ; normal → 1.0
        if (($hour >= 7 && $hour < 9) || ($hour >= 17 && $hour < 20)) {
            return 1.4;
        }

        if ($hour >= 12 && $hour < 14) {
            return 1.25;
        }

        // Nuit (22h-5h) : plus fluide
        if ($hour >= 22 || $hour < 5) {
            return 0.85;
        }

        return 1.0;
    }

    private function getWeatherMultiplier(float $weatherScore): float
    {
        // Score 0-100 → multiplicateur 1.0 (bon) à 2.5 (catastrophique)
        return match(true) {
            $weatherScore >= 80 => 1.0,
            $weatherScore >= 60 => 1.15,
            $weatherScore >= 40 => 1.40,
            $weatherScore >= 20 => 1.85,
            default             => 2.50,
        };
    }

    private function getTrafficMultiplier(float $trafficScore): float
    {
        // Score 0-100 (100 = fluide) → multiplicateur
        return match(true) {
            $trafficScore >= 80 => self::TRAFFIC_MULTIPLIERS['clear'],
            $trafficScore >= 60 => self::TRAFFIC_MULTIPLIERS['low'],
            $trafficScore >= 40 => self::TRAFFIC_MULTIPLIERS['moderate'],
            $trafficScore >= 20 => self::TRAFFIC_MULTIPLIERS['high'],
            default             => self::TRAFFIC_MULTIPLIERS['severe'],
        };
    }

    private function defaultETA(string $reason): array
    {
        return [
            'minutes'     => 30,
            'optimistic'  => 20,
            'pessimistic' => 45,
            'confidence'  => 0.3,
            'breakdown'   => [],
            'factors'     => ['fallback_reason' => $reason],
        ];
    }
}
