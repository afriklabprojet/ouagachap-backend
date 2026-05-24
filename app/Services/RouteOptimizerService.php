<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use App\Traits\CalculatesDistance;
use Illuminate\Support\Collection;

/**
 * RouteOptimizerService
 *
 * Optimise l'ordre de livraison pour un coursier avec plusieurs commandes actives.
 * Utilise l'algorithme du plus proche voisin (Nearest-Neighbor Greedy TSP).
 * Adapté aux livraisons séquentielles : pickup → delivery par commande.
 */
final class RouteOptimizerService
{
    use CalculatesDistance;

    // Statuts considérés comme "en cours" pour un coursier
    private const ACTIVE_STATUSES = [
        OrderStatus::ASSIGNED,
        OrderStatus::ACCEPTED,
        OrderStatus::PICKING_UP,
        OrderStatus::PICKED_UP,
        OrderStatus::IN_TRANSIT,
    ];

    public function __construct(
        protected DelayPredictorService  $delayPredictorService,
        protected TrafficAnalysisService $trafficAnalysisService,
    ) {}

    /**
     * Retourne le plan de route optimisé pour un coursier.
     *
     * @return array{
     *   total_distance_km: float,
     *   total_eta_minutes: int,
     *   stops: array,
     *   order_count: int,
     *   already_optimized: bool
     * }
     */
    public function optimizeRoute(User $courier): array
    {
        if (! $courier->current_latitude || ! $courier->current_longitude) {
            return $this->emptyRoute('Position GPS du coursier inconnue');
        }

        $orders = $this->getActiveOrders($courier);

        if ($orders->isEmpty()) {
            return $this->emptyRoute('Aucune commande active');
        }

        if ($orders->count() === 1) {
            return $this->singleOrderRoute($courier, $orders->first());
        }

        return $this->nearestNeighborRoute($courier, $orders);
    }

    /**
     * Estime le temps de complétion pour toutes les livraisons actives.
     *
     * @return array{minutes: int, stops_count: int}
     */
    public function estimateTotalCompletion(User $courier): array
    {
        $route = $this->optimizeRoute($courier);

        return [
            'minutes'     => $route['total_eta_minutes'],
            'stops_count' => count($route['stops']),
        ];
    }

    /**
     * Indique si le coursier a des commandes qui nécessitent une re-optimisation
     * (ex : nouvelle commande ajoutée).
     */
    public function needsReOptimization(User $courier): bool
    {
        $orders = $this->getActiveOrders($courier);
        return $orders->count() > 1;
    }

    // ─────────────────────────────────────────────────────────────
    // Algorithme Nearest-Neighbor (TSP greedy)
    // ─────────────────────────────────────────────────────────────

    /**
     * Nearest-Neighbor pour les livraisons séquentielles :
     * Pour chaque commande non ramassée, on doit d'abord aller au pickup
     * puis à la delivery. Pour les commandes déjà ramassées (PICKED_UP/IN_TRANSIT),
     * on va directement à la delivery.
     */
    private function nearestNeighborRoute(User $courier, Collection $orders): array
    {
        $currentLat = (float) $courier->current_latitude;
        $currentLng = (float) $courier->current_longitude;

        // Construire la liste des stops à visiter
        $stops = $this->buildStopsFromOrders($orders);

        $ordered  = [];
        $visited  = [];
        $totalDist = 0.0;
        $totalEta  = 0;

        while (count($visited) < count($stops)) {
            $bestIdx  = null;
            $bestDist = PHP_FLOAT_MAX;

            foreach ($stops as $idx => $stop) {
                if (isset($visited[$idx])) {
                    continue;
                }

                // Respecter les contraintes de séquence : pickup avant delivery
                if (
                    $stop['type'] === 'delivery' &&
                    ! isset($visited[$stop['pickup_stop_idx']])
                ) {
                    continue;
                }

                $dist = $this->calculateDistanceKm(
                    $currentLat,
                    $currentLng,
                    $stop['latitude'],
                    $stop['longitude']
                );

                // Pénaliser si la route est impactée par le trafic
                $trafficScore = $this->trafficAnalysisService->getTrafficImpactScore(
                    $stop['latitude'],
                    $stop['longitude']
                );

                // Normaliser : mauvais trafic (score bas) augmente la distance effective
                $effectiveDist = $dist * (1 + (100 - $trafficScore) / 200);

                if ($effectiveDist < $bestDist) {
                    $bestDist = $effectiveDist;
                    $bestIdx  = $idx;
                }
            }

            if ($bestIdx === null) {
                break;
            }

            $stop = $stops[$bestIdx];
            $segmentDist = $this->calculateDistanceKm(
                $currentLat,
                $currentLng,
                $stop['latitude'],
                $stop['longitude']
            );

            // ETA pour ce segment (~25 km/h moto)
            $segmentEta = (int) ceil(($segmentDist / 25.0) * 60);

            $ordered[] = array_merge($stop, [
                'segment_distance_km' => round($segmentDist, 2),
                'segment_eta_minutes' => $segmentEta,
            ]);

            $totalDist += $segmentDist;
            $totalEta  += $segmentEta;

            $currentLat = $stop['latitude'];
            $currentLng = $stop['longitude'];
            $visited[$bestIdx] = true;
        }

        return [
            'total_distance_km' => round($totalDist, 2),
            'total_eta_minutes' => $totalEta + (count($orders) * 3), // +3 min buffer/stop
            'stops'             => $ordered,
            'order_count'       => $orders->count(),
            'already_optimized' => false,
        ];
    }

    /**
     * Convertit les commandes actives en liste de stops à visiter.
     * Pour les commandes non encore ramassées : [pickup, delivery]
     * Pour les commandes déjà ramassées      : [delivery seulement]
     */
    private function buildStopsFromOrders(Collection $orders): array
    {
        $stops = [];
        $idx   = 0;

        foreach ($orders as $order) {
            $needsPickup = in_array($order->status, [
                OrderStatus::ASSIGNED,
                OrderStatus::ACCEPTED,
                OrderStatus::PICKING_UP,
            ]);

            $pickupIdx = null;

            if ($needsPickup) {
                $stops[$idx] = [
                    'type'      => 'pickup',
                    'order_id'  => $order->id,
                    'latitude'  => (float) $order->pickup_latitude,
                    'longitude' => (float) $order->pickup_longitude,
                    'address'   => $order->pickup_address ?? '',
                    'label'     => "Récupération #" . $order->id,
                ];
                $pickupIdx = $idx;
                $idx++;
            }

            $stops[$idx] = [
                'type'            => 'delivery',
                'order_id'        => $order->id,
                'latitude'        => (float) $order->dropoff_latitude,
                'longitude'       => (float) $order->dropoff_longitude,
                'address'         => $order->dropoff_address ?? '',
                'label'           => "Livraison #" . $order->id,
                'pickup_stop_idx' => $pickupIdx ?? -1, // -1 = déjà ramassé
            ];
            $idx++;
        }

        return $stops;
    }

    // ─────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────

    private function getActiveOrders(User $courier): Collection
    {
        return Order::where('courier_id', $courier->id)
            ->whereIn('status', array_map(fn($s) => $s->value, self::ACTIVE_STATUSES))
            ->whereNotNull('dropoff_latitude')
            ->orderBy('created_at')
            ->get();
    }

    private function singleOrderRoute(User $courier, Order $order): array
    {
        $eta = $this->delayPredictorService->predictETA($order, $courier);

        $stops = $this->buildStopsFromOrders(collect([$order]));

        return [
            'total_distance_km' => $eta['breakdown']['total_distance_km'] ?? 0,
            'total_eta_minutes' => $eta['minutes'],
            'stops'             => array_values($stops),
            'order_count'       => 1,
            'already_optimized' => true,
        ];
    }

    private function emptyRoute(string $reason): array
    {
        return [
            'total_distance_km' => 0.0,
            'total_eta_minutes' => 0,
            'stops'             => [],
            'order_count'       => 0,
            'already_optimized' => true,
            'message'           => $reason,
        ];
    }
}
