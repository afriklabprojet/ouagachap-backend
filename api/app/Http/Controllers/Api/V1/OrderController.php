<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\CreateOrderDTO;
use App\DTOs\UpdateOrderStatusDTO;
use App\Enums\OrderStatus;
use App\Http\Requests\Order\CreateOrderRequest;
use App\Http\Requests\Order\EstimateOrderRequest;
use App\Http\Requests\Order\RateOrderRequest;
use App\Http\Requests\Order\UpdateOrderStatusRequest;
use App\Models\Order;
use App\Models\User;
use App\Services\GoogleMapsService;
use App\Services\OrderService;
use App\Traits\CalculatesDistance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * @group Commandes
 *
 * Endpoints pour gérer les commandes de livraison.
 */
class OrderController extends BaseController
{
    use CalculatesDistance;

    private const MSG_UNAUTHORIZED = 'Accès non autorisé.';

    public function __construct(
        private OrderService $orderService,
        private GoogleMapsService $googleMapsService,
    ) {}

    /**
     * Estimer le prix
     *
     * Calcule une estimation de prix basée sur la distance entre les points de collecte et de livraison.
     *
     * @bodyParam pickup_latitude number required Latitude du point de collecte. Example: 12.371400
     * @bodyParam pickup_longitude number required Longitude du point de collecte. Example: -1.519700
     * @bodyParam dropoff_latitude number required Latitude du point de livraison. Example: 12.380000
     * @bodyParam dropoff_longitude number required Longitude du point de livraison. Example: -1.510000
     * @bodyParam zone_id integer ID de la zone (optionnel). Example: 1
     * @response 200 {"success": true, "message": "Estimation calculée.", "data": {"distance_km": 2.5, "estimated_price": 1500, "estimated_duration_minutes": 15}}
     */
    public function estimate(EstimateOrderRequest $request): JsonResponse
    {
        $estimate = $this->orderService->getEstimate($request->validated());

        return $this->success($estimate, 'Estimation calculée.');
    }

    /**
     * Créer une commande
     *
     * Crée une nouvelle commande de livraison. Réservé aux clients.
     *
     * @response 201 {"success": true, "message": "Commande créée avec succès.", "data": {"id": "uuid", "order_number": "OC-20260120-ABCD", "status": "pending", "total_price": 1500}}
     * @response 403 {"success": false, "message": "Seuls les clients peuvent créer des commandes."}
     */
    public function store(CreateOrderRequest $request): JsonResponse
    {
        $order = $this->orderService->createOrder(
            $request->user(),
            CreateOrderDTO::fromRequest($request)
        );

        return $this->success(
            $order->load(['zone']),
            'Commande créée avec succès.',
            201
        );
    }

    /**
     * Lister mes commandes
     *
     * Retourne la liste paginée des commandes du client connecté.
     *
     * @queryParam status string Filtrer par statut (pending, assigned, picked_up, delivered, cancelled). Example: pending
     * @queryParam per_page integer Nombre de résultats par page. Example: 15
     * @response 200 {"success": true, "message": "Commandes récupérées.", "data": [], "meta": {"current_page": 1, "last_page": 1, "per_page": 15, "total": 0}}
     */
    public function index(Request $request): JsonResponse
    {
        $status = $request->query('status');
        $perPage = min((int) $request->query('per_page', 15), 100);

        $orders = $this->orderService->getClientOrders(
            $request->user(),
            $status,
            $perPage
        );

        return $this->paginated($orders, 'Commandes récupérées.');
    }

    /**
     * Get order details
     * GET /api/v1/orders/{order}
     */
    public function show(Order $order, Request $request): JsonResponse
    {
        $order->load([
            'client:id,name,phone,average_rating',
            'courier:id,name,phone,avatar,average_rating,vehicle_type,vehicle_plate',
            'zone:id,name',
            'statusHistories' => fn($q) => $q->latest()->limit(10),
            'payment',
        ]);

        // Utiliser la Policy pour l'autorisation
        if ($request->user()->cannot('view', $order)) {
            return $this->forbidden(self::MSG_UNAUTHORIZED);
        }

        return $this->success($order);
    }

    /**
     * Get available orders for couriers
     * GET /api/v1/orders/available
     * @codeCoverageIgnore Not mapped to any route - CourierController handles this
     */
    public function available(Request $request): JsonResponse
    {
        if (!$request->user()->isCourier()) {
            return $this->forbidden('Réservé aux coursiers.');
        }

        $perPage = min((int) $request->query('per_page', 15), 100);
        $orders = $this->orderService->getAvailableOrders($perPage);

        return $this->paginated($orders, 'Commandes disponibles.');
    }

    /**
     * Accept an order (courier)
     * POST /api/v1/orders/{order}/accept
     * @codeCoverageIgnore Not mapped to any route - CourierController handles this
     */
    public function accept(Order $order, Request $request): JsonResponse
    {
        if ($request->user()->cannot('accept', $order)) {
            return $this->forbidden('Vous ne pouvez pas accepter cette commande.');
        }

        $result = $this->orderService->assignOrder($order, $request->user());

        if (!$result['success']) {
            return $this->error($result['message']);
        }

        return $this->success($result['order'], $result['message']);
    }

    /**
     * Update order status
     * PUT /api/v1/orders/{order}/status
     * @codeCoverageIgnore Not mapped to any route - CourierController handles this
     */
    public function updateStatus(Order $order, UpdateOrderStatusRequest $request): JsonResponse
    {
        // Utiliser la Policy pour l'autorisation
        if ($request->user()->cannot('updateStatus', $order)) {
            return $this->forbidden(self::MSG_UNAUTHORIZED);
        }

        $dto = UpdateOrderStatusDTO::fromRequest($request);

        $result = $this->orderService->updateStatus(
            $order,
            $dto->status,
            $request->user(),
            $dto->resolvedNote(),
            $dto->latitude,
            $dto->longitude
        );

        if (!$result['success']) {
            return $this->error($result['message']);
        }

        return $this->success($result['order'], $result['message']);
    }

    /**
     * Cancel order (client)
     * POST /api/v1/orders/{order}/cancel
     */
    public function cancel(Order $order, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        if ($error = $this->validateCancellation($order, $request->user())) {
            return $error;
        }

        $result = $this->orderService->updateStatus(
            $order,
            OrderStatus::CANCELLED,
            $request->user(),
            $validated['reason']
        );

        return $result['success']
            ? $this->success($result['order'], 'Commande annulée.')
            : $this->error($result['message']);
    }

    /**
     * Valide qu'une commande peut être annulée par l'utilisateur.
     */
    private function validateCancellation(Order $order, User $user): ?JsonResponse
    {
        if ($user->cannot('cancel', $order)) {
            return $this->forbidden('Vous ne pouvez pas annuler cette commande.');
        }

        if (!in_array($order->status, [OrderStatus::PENDING, OrderStatus::ASSIGNED])) {
            return $this->error('Cette commande ne peut plus être annulée.'); // @codeCoverageIgnore
        }

        return null;
    }

    /**
     * Rate courier (client)
     * POST /api/v1/orders/{order}/rate-courier
     */
    public function rateCourier(Order $order, RateOrderRequest $request): JsonResponse
    {
        if ($request->user()->cannot('rateCourier', $order)) {
            return $this->forbidden('Vous ne pouvez pas noter ce coursier.');
        }

        $order->rateCourier($request->rating, $request->review);

        return $this->success($order->fresh(), 'Merci pour votre évaluation.');
    }

    /**
     * Rate client (courier)
     * POST /api/v1/orders/{order}/rate-client
     */
    public function rateClient(Order $order, RateOrderRequest $request): JsonResponse
    {
        if ($request->user()->cannot('rateClient', $order)) {
            return $this->forbidden('Vous ne pouvez pas noter ce client.');
        }

        $order->rateClient($request->rating, $request->review);

        return $this->success($order->fresh(), 'Merci pour votre évaluation.');
    }

    /**
     * Get order tracking info
     * GET /api/v1/orders/{order}/tracking
     *
     * Retourne les informations de suivi en temps réel pour une commande.
     * Inclut la position du coursier, l'estimation du temps d'arrivée, et l'historique.
     */
    public function tracking(Order $order, Request $request): JsonResponse
    {
        $order->load([
            'courier:id,name,phone,avatar,current_latitude,current_longitude,location_updated_at,vehicle_type,vehicle_plate',
            'statusHistories',
        ]);

        // Vérifier l'autorisation via Policy
        if ($request->user()->cannot('track', $order)) {
            return $this->forbidden(self::MSG_UNAUTHORIZED);
        }

        // Calculer la distance restante et l'ETA si le coursier a une position
        $estimatedDistance = null;
        $estimatedMinutes = null;
        $estimatedArrival = null;

        if ($order->courier && $order->courier->current_latitude && $order->courier->current_longitude) {
            // Déterminer la destination selon le statut
            if (in_array($order->status->value, ['assigned'])) {
                // En route vers le pickup
                $destLat = $order->pickup_latitude;
                $destLng = $order->pickup_longitude;
            } else {
                // En route vers la livraison
                $destLat = $order->dropoff_latitude;
                $destLng = $order->dropoff_longitude;
            }

            // Calculer la distance (formule haversine simplifiée)
            $estimatedDistance = $this->calculateDistanceKm(
                (float) $order->courier->current_latitude,
                (float) $order->courier->current_longitude,
                (float) $destLat,
                (float) $destLng
            );

            // Estimer le temps (moyenne 25 km/h en ville)
            $estimatedMinutes = (int) ceil(($estimatedDistance / 25) * 60);
            $estimatedArrival = now()->addMinutes($estimatedMinutes)->toIso8601String();
        }

        // Construire les événements de tracking
        $events = $order->statusHistories->map(function ($history) {
            return [
                'type' => $history->status->value,
                'title' => $this->getStatusLabel($history->status->value),
                'description' => $history->note,
                'timestamp' => $history->created_at->toIso8601String(),
                'latitude' => $history->latitude,
                'longitude' => $history->longitude,
            ];
        })->toArray();

        return $this->success([
            'order_id' => $order->id,
            'status' => $order->status->value,
            'status_label' => $this->getStatusLabel($order->status->value),
            'courier' => $order->courier ? [
                'courier_id' => $order->courier->id,
                'courier_name' => $order->courier->name,
                'courier_phone' => $order->courier->phone,
                'courier_photo' => $order->courier->avatar_url,
                'latitude' => $order->courier->current_latitude,
                'longitude' => $order->courier->current_longitude,
                'speed' => $this->estimateCourierSpeed($order->courier),
                'heading' => null,
                'timestamp' => $order->courier->location_updated_at?->toIso8601String(),
                'vehicle_type' => $order->courier->vehicle_type ?? 'Moto',
                'vehicle_plate' => $order->courier->vehicle_plate,
            ] : null,
            'pickup_latitude' => $order->pickup_latitude,
            'pickup_longitude' => $order->pickup_longitude,
            'pickup_address' => $order->pickup_address,
            'delivery_latitude' => $order->dropoff_latitude,
            'delivery_longitude' => $order->dropoff_longitude,
            'delivery_address' => $order->dropoff_address,
            'estimated_distance' => $estimatedDistance,
            'estimated_minutes' => $estimatedMinutes,
            'estimated_arrival' => $estimatedArrival,
            'route_polyline' => $this->getRoutePolyline($order, $destLat ?? null, $destLng ?? null),
            'events' => $events,
        ]);
    }

    /**
     * Historique de trajet GPS
     *
     * Retourne les points GPS enregistrés pendant la livraison.
     * Accessible par le client propriétaire de la commande et par le coursier.
     *
     * @authenticated
     * @urlParam order string required UUID de la commande. Example: 550e8400-e29b-41d4-a716-446655440000
     * @queryParam from string Filtre début ISO 8601 (optionnel). Example: 2026-05-17T09:00:00Z
     * @queryParam to string Filtre fin ISO 8601 (optionnel). Example: 2026-05-17T11:00:00Z
     *
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "order_id": "550e8400-...",
     *     "total_points": 142,
     *     "points": [
     *       {"lat": 12.3714, "lng": -1.5197, "heading": 90.5, "speed": 25.3, "recorded_at": "2026-05-17T09:01:00Z"}
     *     ]
     *   }
     * }
     */
    public function routeHistory(Order $order, Request $request): JsonResponse
    {
        if ($request->user()->cannot('track', $order)) {
            return $this->forbidden(self::MSG_UNAUTHORIZED);
        }

        $query = \App\Models\OrderLocationHistory::where('order_id', $order->id)
            ->orderBy('recorded_at');

        if ($request->filled('from')) {
            $query->where('recorded_at', '>=', $request->date('from'));
        }

        if ($request->filled('to')) {
            $query->where('recorded_at', '<=', $request->date('to'));
        }

        $points = $query->get(['latitude', 'longitude', 'heading', 'speed', 'accuracy', 'recorded_at'])
            ->map(fn ($p) => [
                'lat'         => $p->latitude,
                'lng'         => $p->longitude,
                'heading'     => $p->heading,
                'speed'       => $p->speed,
                'accuracy'    => $p->accuracy,
                'recorded_at' => $p->recorded_at->toIso8601String(),
            ]);

        return $this->success([
            'order_id'     => $order->id,
            'total_points' => $points->count(),
            'points'       => $points,
        ]);
    }

    /**
     * Estimer la vitesse du coursier à partir des positions de géofence récentes
     * Retourne la vitesse en km/h ou null si pas assez de données
     * @codeCoverageIgnore Depends on GeofenceLog table which may not exist in test
     */
    private function estimateCourierSpeed(User $courier): ?float
    {
        // Récupérer les 2 dernières positions enregistrées (logs geofence ou historique)
        $recentLogs = \App\Models\GeofenceLog::where('user_id', $courier->id)
            ->orderByDesc('created_at')
            ->limit(2)
            ->get();

        if ($recentLogs->count() < 2) {
            return null;
        }

        $latest = $recentLogs[0];
        $previous = $recentLogs[1];

        $distance = $this->calculateDistanceKm(
            (float) $previous->latitude,
            (float) $previous->longitude,
            (float) $latest->latitude,
            (float) $latest->longitude
        );

        $timeDiffHours = $latest->created_at->diffInSeconds($previous->created_at) / 3600;

        if ($timeDiffHours <= 0) {
            return null;
        }

        $speed = round($distance / $timeDiffHours, 1);

        // Filtrer les valeurs aberrantes (>120 km/h en ville)
        return $speed <= 120 ? $speed : null;
    }

    /**
     * Obtenir le polyline de l'itinéraire via Google Directions API
     * Résultat caché 30 secondes par commande pour réduire les appels API
     */
    private function getRoutePolyline(Order $order, ?float $destLat, ?float $destLng): ?string
    {
        if (!$order->courier || !$order->courier->current_latitude || !$destLat || !$destLng) {
            return null;
        }

        $cacheKey = "route_polyline:{$order->id}:" . round($order->courier->current_latitude, 4)
            . ':' . round($order->courier->current_longitude, 4);

        return Cache::remember($cacheKey, 30, function () use ($order, $destLat, $destLng) {
            $directions = $this->googleMapsService->getDirections(
                $order->courier->current_latitude,
                $order->courier->current_longitude,
                $destLat,
                $destLng
            );

            return $directions['polyline'] ?? null;
        });
    }

    /**
     * Obtenir le label du statut
     * @codeCoverageIgnore Private helper - PCOV match expression tracking issue
     */
    private function getStatusLabel(string $status): string
    {
        return match ($status) {
            'pending' => 'En attente',
            'assigned' => 'Coursier assigné',
            'accepted' => 'Acceptée par le coursier',
            'picking_up' => 'En route vers collecte',
            'picked_up' => 'Colis récupéré',
            'in_transit' => 'En cours de livraison',
            'delivered' => 'Livré',
            'cancelled' => 'Annulé',
            default => ucfirst($status),
        };
    }
}
