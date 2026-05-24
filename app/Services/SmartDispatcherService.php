<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Orchestrateur intelligent d'assignation automatique de commandes.
 *
 * Utilise CourierMatchingService (scoring multi-critères) et OrderService (assignation transactionnelle)
 * pour trouver et assigner le meilleur coursier disponible à chaque commande.
 */
class SmartDispatcherService
{
    /** Score minimum pour qu'un coursier soit éligible au dispatch automatique */
    private const MIN_DISPATCH_SCORE = 20.0;

    /** Rayon de recherche par défaut (km) */
    private const DEFAULT_RADIUS_KM = 10.0;

    /** Rayon étendu en cas d'absence de coursier dans le rayon par défaut */
    private const EXTENDED_RADIUS_KM = 20.0;

    public function __construct(
        protected CourierMatchingService $courierService,
        protected OrderService $orderService,
        protected WeatherService $weatherService,
        protected TrafficAnalysisService $trafficAnalysisService,
    ) {}

    // =========================================================================
    // DISPATCH PRINCIPAL
    // =========================================================================

    /**
     * Dispatcher intelligemment une commande vers le meilleur coursier disponible.
     *
     * @return array{
     *   success: bool,
     *   message: string,
     *   courier?: User,
     *   score?: float,
     *   breakdown?: array,
     *   suggestions_count?: int
     * }
     */
    public function dispatchOrder(Order $order): array
    {
        if (! $order->isPending()) {
            return [
                'success' => false,
                'message' => 'La commande n\'est pas en attente de dispatch (statut: ' . $order->status->value . ').',
            ];
        }

        if (! $order->pickup_latitude || ! $order->pickup_longitude) {
            return [
                'success' => false,
                'message' => 'Coordonnées GPS de collecte manquantes pour cette commande.',
            ];
        }

        // 1. Récupérer les candidats dans le rayon normal
        $candidates = $this->getScoredCandidates($order, self::DEFAULT_RADIUS_KM, limit: 5);

        // 2. Si aucun candidat, élargir le rayon
        if ($candidates->isEmpty()) {
            Log::info("SmartDispatcher: aucun coursier dans {self::DEFAULT_RADIUS_KM}km pour commande #{$order->id}, élargissement à " . self::EXTENDED_RADIUS_KM . "km");
            $candidates = $this->getScoredCandidates($order, self::EXTENDED_RADIUS_KM, limit: 5);
        }

        if ($candidates->isEmpty()) {
            return [
                'success' => false,
                'message' => 'Aucun coursier disponible dans un rayon de ' . self::EXTENDED_RADIUS_KM . ' km.',
            ];
        }

        // 3. Prendre le meilleur candidat (premier après tri par score décroissant)
        $best = $candidates->first();

        if (($best['score']['total'] ?? 0) < self::MIN_DISPATCH_SCORE) {
            return [
                'success'          => false,
                'message'          => 'Aucun coursier ne remplit les critères minimaux de qualité.',
                'best_score'       => $best['score']['total'] ?? 0,
                'min_score_needed' => self::MIN_DISPATCH_SCORE,
            ];
        }

        /** @var User $courier */
        $courier = $best['courier'];

        // 4. Assigner via OrderService (transaction DB + event FCM)
        $result = $this->orderService->assignOrder($order, $courier);

        if ($result['success']) {
            Log::info("SmartDispatcher: commande #{$order->id} assignée au coursier #{$courier->id} (score: {$best['score']['total']})");

            return [
                'success'           => true,
                'message'           => "Commande assignée au coursier {$courier->name}.",
                'courier'           => $courier,
                'score'             => $best['score']['total'],
                'breakdown'         => $best['score']['breakdown'] ?? [],
                'suggestions_count' => $candidates->count(),
            ];
        }

        // L'assignation a échoué (ex: coursier déjà pris par une autre requête concurrente)
        Log::warning("SmartDispatcher: échec assignation commande #{$order->id} au coursier #{$courier->id}: {$result['message']}");

        return $result;
    }

    // =========================================================================
    // AUTO-DISPATCH EN LOT
    // =========================================================================

    /**
     * Traiter automatiquement toutes les commandes PENDING sans coursier.
     * Destiné à être appelé par un Job toutes les N minutes.
     *
     * @return array{dispatched: int, failed: int, skipped: int, details: array}
     */
    public function autoDispatchPending(): array
    {
        $pendingOrders = Order::where('status', OrderStatus::PENDING)
            ->whereNull('courier_id')
            ->whereNotNull('pickup_latitude')
            ->whereNotNull('pickup_longitude')
            ->orderBy('created_at')  // FIFO: plus anciennes d'abord
            ->get();

        $stats = [
            'dispatched' => 0,
            'failed'     => 0,
            'skipped'    => 0,
            'details'    => [],
        ];

        if ($pendingOrders->isEmpty()) {
            return $stats;
        }

        // Vérifier si les conditions météo sont acceptables
        $weatherScore = $this->weatherService->getDeliveryScore(null, null);
        $weatherOk = $weatherScore >= 15.0; // Seuil absolu (orage violent = 0)

        foreach ($pendingOrders as $order) {
            // Ne pas tenter si météo catastrophique
            if (! $weatherOk) {
                $stats['skipped']++;
                $stats['details'][] = [
                    'order_id' => $order->id,
                    'status'   => 'skipped',
                    'reason'   => "Météo dangereuse (score: {$weatherScore})",
                ];
                continue;
            }

            $result = $this->dispatchOrder($order);

            if ($result['success']) {
                $stats['dispatched']++;
                $stats['details'][] = [
                    'order_id'   => $order->id,
                    'status'     => 'dispatched',
                    'courier_id' => $result['courier']->id ?? null,
                    'score'      => $result['score'] ?? null,
                ];
            } else {
                $stats['failed']++;
                $stats['details'][] = [
                    'order_id' => $order->id,
                    'status'   => 'failed',
                    'reason'   => $result['message'],
                ];
            }
        }

        Log::info('SmartDispatcher autoDispatch: ' . json_encode([
            'total'      => $pendingOrders->count(),
            'dispatched' => $stats['dispatched'],
            'failed'     => $stats['failed'],
            'skipped'    => $stats['skipped'],
        ]));

        return $stats;
    }

    // =========================================================================
    // SUGGESTIONS
    // =========================================================================

    /**
     * Retourne les N meilleurs candidats pour une commande (sans assigner).
     * Utile pour affichage admin avant confirmation manuelle.
     *
     * @return Collection<int, array{courier: User, score: array, distance_km: float}>
     */
    public function getDispatchSuggestions(Order $order, int $limit = 3): Collection
    {
        return $this->getScoredCandidates($order, self::DEFAULT_RADIUS_KM, $limit);
    }

    /**
     * Retourne les informations contextuelles de dispatch pour l'admin.
     * Inclut météo, incidents trafic actifs et nombre de coursiers dispo.
     */
    public function getDispatchContext(Order $order): array
    {
        $weatherScore = $this->weatherService->getDeliveryScore(
            $order->pickup_latitude ? (float) $order->pickup_latitude : null,
            $order->pickup_longitude ? (float) $order->pickup_longitude : null
        );

        $trafficScore = 100.0;
        $nearbyIncidents = [];
        if ($order->pickup_latitude && $order->pickup_longitude) {
            $trafficScore    = $this->trafficAnalysisService->getTrafficImpactScore(
                (float) $order->pickup_latitude,
                (float) $order->pickup_longitude
            );
            $nearbyIncidents = $this->trafficAnalysisService->getNearbyIncidents(
                (float) $order->pickup_latitude,
                (float) $order->pickup_longitude,
                radiusKm: 3.0
            );
        }

        $availableCouriers = $this->getScoredCandidates($order, self::DEFAULT_RADIUS_KM, limit: 10);

        return [
            'weather' => [
                'score'    => $weatherScore,
                'status'   => $this->weatherScoreLabel($weatherScore),
                'dispatch_ok' => $weatherScore >= 15.0,
            ],
            'traffic' => [
                'score'           => $trafficScore,
                'status'          => $this->trafficScoreLabel($trafficScore),
                'nearby_incidents' => count($nearbyIncidents),
            ],
            'couriers' => [
                'available_in_radius' => $availableCouriers->count(),
                'best_score'          => $availableCouriers->first()['score']['total'] ?? null,
            ],
            'route_severely_impacted' => $order->dropoff_latitude && $order->dropoff_longitude
                ? $this->trafficAnalysisService->isRouteSeverelyImpacted(
                    (float) $order->pickup_latitude,
                    (float) $order->pickup_longitude,
                    (float) $order->dropoff_latitude,
                    (float) $order->dropoff_longitude
                )
                : false,
        ];
    }

    // =========================================================================
    // HELPERS PRIVÉS
    // =========================================================================

    /**
     * Récupère les candidats scorés et triés pour une commande.
     *
     * @return Collection<int, array{courier: User, score: array, distance_km: float}>
     */
    private function getScoredCandidates(Order $order, float $radiusKm, int $limit): Collection
    {
        $orderDetails = [
            'is_large'   => $order->is_large   ?? false,
            'is_fragile' => $order->is_fragile  ?? false,
            'order_type' => $order->order_type  ?? 'standard',
            'weight'     => $order->weight      ?? 0,
        ];

        // getSmartMatchedCouriers retourne déjà les coursiers triés par score décroissant
        $couriers = $this->courierService->getSmartMatchedCouriers(
            (float) $order->pickup_latitude,
            (float) $order->pickup_longitude,
            $orderDetails,
            radiusKm: $radiusKm,
            limit: $limit
        );

        return $couriers->map(function (User $courier) {
            return [
                'courier'     => $courier,
                'score'       => [
                    'total'     => $courier->matching_score  ?? 0.0,
                    'breakdown' => $courier->score_breakdown ?? [],
                ],
                'distance_km' => round($courier->distance ?? 0, 2),
            ];
        });
    }

    private function weatherScoreLabel(float $score): string
    {
        return match (true) {
            $score >= 80 => 'Excellent',
            $score >= 60 => 'Correct',
            $score >= 30 => 'Difficile',
            $score >= 15 => 'Très difficile',
            default      => 'Dangereux',
        };
    }

    private function trafficScoreLabel(float $score): string
    {
        return match (true) {
            $score >= 80 => 'Fluide',
            $score >= 60 => 'Modéré',
            $score >= 40 => 'Chargé',
            default      => 'Congestionné',
        };
    }
}
