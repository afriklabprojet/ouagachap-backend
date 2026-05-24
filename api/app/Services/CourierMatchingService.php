<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use App\Traits\CalculatesDistance;
use Illuminate\Database\Eloquent\Collection;

/**
 * Algorithme de matching IA — scoring multi-critères pour l'assignation des coursiers.
 *
 * Pondération:
 *  - Distance      35 %
 *  - Note moyenne  20 %
 *  - Réactivité    15 %
 *  - Charge        10 %
 *  - Véhicule      10 %
 *  - Batterie       5 %
 *  - Trafic         3 %
 *  - Météo          2 %
 */
class CourierMatchingService
{
    use CalculatesDistance;

    public function __construct(
        protected WeatherService $weatherService,
        protected TrafficAnalysisService $trafficAnalysisService,
    ) {}

    /**
     * Retourne les coursiers triés par score décroissant.
     *
     * @codeCoverageIgnore MySQL Haversine formula not compatible with SQLite tests
     */
    public function getSmartMatchedCouriers(
        float $latitude,
        float $longitude,
        array $orderDetails = [],
        float $radiusKm = 5,
        int $limit = 10
    ): Collection {
        [$haversine, $bindings] = $this->haversineExpression(
            $latitude, $longitude, 'current_latitude', 'current_longitude',
        );

        $couriers = User::selectRaw("*, {$haversine} AS distance", $bindings)
            ->couriers()
            ->active()
            ->available()
            ->whereNotNull('current_latitude')
            ->whereNotNull('current_longitude')
            ->where('average_rating', '>=', 3.0)
            ->having('distance', '<', $radiusKm)
            ->get();

        if ($couriers->isEmpty()) {
            return $couriers;
        }

        $scored = $couriers->map(function ($courier) use ($radiusKm, $orderDetails) {
            $score = $this->calculateCourierScore($courier, $radiusKm, $orderDetails);
            $courier->matching_score = $score['total'];
            $courier->score_breakdown = $score['breakdown'];

            return $courier;
        });

        return $scored->sortByDesc('matching_score')->take($limit)->values();
    }

    /**
     * Meilleur coursier unique pour une commande.
     *
     * @codeCoverageIgnore Depends on MySQL Haversine getSmartMatchedCouriers
     */
    public function getBestCourierForOrder(Order $order): ?User
    {
        $orderDetails = [
            'is_large'   => $order->is_large ?? false,
            'is_fragile' => $order->is_fragile ?? false,
            'order_type' => $order->order_type ?? 'standard',
            'weight'     => $order->weight ?? 0,
        ];

        return $this->getSmartMatchedCouriers(
            $order->pickup_latitude,
            $order->pickup_longitude,
            $orderDetails,
            radiusKm: 10,
            limit: 1
        )->first();
    }

    /**
     * Score composite d'un coursier (0–100).
     */
    protected function calculateCourierScore(
        User $courier,
        float $maxRadius,
        array $orderDetails = []
    ): array {
        $distance = $courier->distance ?? $maxRadius;
        $distanceScore = max(0, 100 - ($distance / $maxRadius * 100));

        $rating = $courier->average_rating ?? 3.0;
        $totalRatings = $courier->total_ratings ?? 0;
        $ratingConfidence = min(1.0, $totalRatings / 20);
        $ratingScore = ($rating / 5) * 100 * (0.7 + 0.3 * $ratingConfidence);

        $responseScore = $this->calculateResponseScore($courier);

        $activeOrders = $courier->courierOrders()
            ->whereIn('status', OrderStatus::activeStatuses())
            ->count();
        $loadScore = max(0, 100 - ($activeOrders * 50));

        $vehicleScore = $this->calculateVehicleScore($courier, $orderDetails);
        $batteryScore = $this->calculateBatteryScore($courier->battery_level);

        $trafficScore = 100.0;
        if ($courier->current_latitude && $courier->current_longitude) {
            $trafficScore = $this->trafficAnalysisService->getTrafficImpactScore(
                (float) $courier->current_latitude,
                (float) $courier->current_longitude
            );
        }

        $weatherScore = $this->weatherService->getDeliveryScore(
            $courier->current_latitude ? (float) $courier->current_latitude : null,
            $courier->current_longitude ? (float) $courier->current_longitude : null
        );

        $weights = [
            'distance' => 0.35, 'rating'  => 0.20, 'response' => 0.15,
            'load'     => 0.10, 'vehicle' => 0.10, 'battery'  => 0.05,
            'traffic'  => 0.03, 'weather' => 0.02,
        ];

        $totalScore =
            ($distanceScore * $weights['distance']) +
            ($ratingScore   * $weights['rating'])   +
            ($responseScore * $weights['response']) +
            ($loadScore     * $weights['load'])     +
            ($vehicleScore  * $weights['vehicle'])  +
            ($batteryScore  * $weights['battery'])  +
            ($trafficScore  * $weights['traffic'])  +
            ($weatherScore  * $weights['weather']);

        $batteryDetail = $courier->battery_level !== null ? $courier->battery_level.'%' : 'inconnu';

        return [
            'total'     => round($totalScore, 2),
            'breakdown' => [
                'distance' => ['score' => round($distanceScore, 1), 'weight' => $weights['distance'], 'weighted' => round($distanceScore * $weights['distance'], 1), 'detail' => round($distance, 2).' km'],
                'rating'   => ['score' => round($ratingScore, 1),   'weight' => $weights['rating'],   'weighted' => round($ratingScore   * $weights['rating'],   1), 'detail' => "{$rating}/5 ({$totalRatings} avis)"],
                'response' => ['score' => round($responseScore, 1), 'weight' => $weights['response'], 'weighted' => round($responseScore * $weights['response'], 1)],
                'load'     => ['score' => round($loadScore, 1),     'weight' => $weights['load'],     'weighted' => round($loadScore     * $weights['load'],     1), 'detail' => "{$activeOrders} commande(s) active(s)"],
                'vehicle'  => ['score' => round($vehicleScore, 1),  'weight' => $weights['vehicle'],  'weighted' => round($vehicleScore  * $weights['vehicle'],  1), 'detail' => $courier->vehicle_type ?? 'moto'],
                'battery'  => ['score' => round($batteryScore, 1),  'weight' => $weights['battery'],  'weighted' => round($batteryScore  * $weights['battery'],  1), 'detail' => $batteryDetail],
                'traffic'  => ['score' => round($trafficScore, 1),  'weight' => $weights['traffic'],  'weighted' => round($trafficScore  * $weights['traffic'],  1)],
                'weather'  => ['score' => round($weatherScore, 1),  'weight' => $weights['weather'],  'weighted' => round($weatherScore  * $weights['weather'],  1)],
            ],
        ];
    }

    /**
     * @param  int|null  $batteryLevel  0-100, null si inconnu
     */
    protected function calculateBatteryScore(?int $batteryLevel): float
    {
        if ($batteryLevel === null) {
            return 50.0;
        }

        return match (true) {
            $batteryLevel < 15 => 0.0,
            $batteryLevel < 30 => 30.0,
            $batteryLevel < 50 => 60.0,
            default            => 100.0,
        };
    }

    protected function calculateResponseScore(User $courier): float
    {
        $recentOrders = Order::where('courier_id', $courier->id)
            ->whereNotNull('assigned_at')
            ->orderBy('created_at', 'desc')
            ->limit(30)
            ->get();

        if ($recentOrders->isEmpty()) {
            return 70.0;
        }

        $completed = $recentOrders->where('status', OrderStatus::DELIVERED)->count();

        return ($completed / $recentOrders->count()) * 100;
    }

    protected function calculateVehicleScore(User $courier, array $orderDetails): float
    {
        $vehicleType = $courier->vehicle_type ?? 'moto';
        $score = 80.0 + $this->getVehicleAdjustment($vehicleType, $orderDetails);

        return max(0, min(100, $score));
    }

    private function getVehicleAdjustment(string $vehicleType, array $orderDetails): float
    {
        $isLarge   = $orderDetails['is_large']   ?? false;
        $isFragile = $orderDetails['is_fragile']  ?? false;
        $orderType = $orderDetails['order_type']  ?? 'standard';
        $weight    = (float) ($orderDetails['weight'] ?? 0);

        return match ($vehicleType) {
            'moto'                    => $this->motoAdjustment($isLarge, $weight, $orderType),
            'tricycle'                => $this->tricycleAdjustment($isLarge, $weight),
            'voiture', 'car'          => $this->carAdjustment($isLarge, $isFragile, $weight, $orderType),
            'camionnette', 'van'      => $this->vanAdjustment($isLarge, $weight),
            default                   => 0.0,
        };
    }

    /** Moto: idéal petits colis et food. */
    private function motoAdjustment(bool $isLarge, float $weight, string $orderType): float
    {
        $adj = ($isLarge || $weight > 20) ? -40.0 : 0.0;

        return $adj + ($orderType === 'food' ? 10.0 : 0.0);
    }

    /** Tricycle: bon pour colis moyens. */
    private function tricycleAdjustment(bool $isLarge, float $weight): float
    {
        return ($isLarge ? 10.0 : 0.0) + ($weight > 10 && $weight <= 50 ? 10.0 : 0.0);
    }

    /** Voiture/car: idéal gros colis et fragiles. */
    private function carAdjustment(bool $isLarge, bool $isFragile, float $weight, string $orderType): float
    {
        return ($isLarge ? 20.0 : 0.0)
            + ($isFragile ? 15.0 : 0.0)
            + ($weight > 30 ? 15.0 : 0.0)
            + ($orderType === 'food' ? -10.0 : 0.0);
    }

    /** Camionnette/van: parfait pour gros volumes. */
    private function vanAdjustment(bool $isLarge, float $weight): float
    {
        return ($isLarge ? 25.0 : 0.0)
            + ($weight > 50 ? 20.0 : 0.0)
            + (! $isLarge && $weight < 10 ? -20.0 : 0.0);
    }
}
