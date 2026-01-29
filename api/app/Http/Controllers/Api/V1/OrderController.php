<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\OrderStatus;
use App\Http\Requests\Order\CreateOrderRequest;
use App\Http\Requests\Order\RateOrderRequest;
use App\Http\Requests\Order\UpdateOrderStatusRequest;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Commandes
 *
 * Endpoints pour gérer les commandes de livraison.
 */
class OrderController extends BaseController
{
    public function __construct(
        private OrderService $orderService
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
    public function estimate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pickup_latitude' => ['required', 'numeric'],
            'pickup_longitude' => ['required', 'numeric'],
            'dropoff_latitude' => ['required', 'numeric'],
            'dropoff_longitude' => ['required', 'numeric'],
            'zone_id' => ['sometimes', 'exists:zones,id'],
        ]);

        $estimate = $this->orderService->getEstimate($validated);

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
            $request->validated()
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
        $perPage = $request->query('per_page', 15);

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
    public function show(string $orderId, Request $request): JsonResponse
    {
        $order = $this->orderService->getOrderDetails($orderId);

        if (!$order) {
            return $this->notFound('Commande non trouvée.');
        }

        // Check authorization
        $user = $request->user();
        if ($order->client_id !== $user->id && $order->courier_id !== $user->id && !$user->isAdmin()) {
            return $this->forbidden('Accès non autorisé.');
        }

        return $this->success($order);
    }

    /**
     * Get available orders for couriers
     * GET /api/v1/orders/available
     */
    public function available(Request $request): JsonResponse
    {
        if (!$request->user()->isCourier()) {
            return $this->forbidden('Réservé aux coursiers.');
        }

        $perPage = $request->query('per_page', 15);
        $orders = $this->orderService->getAvailableOrders($perPage);

        return $this->paginated($orders, 'Commandes disponibles.');
    }

    /**
     * Accept an order (courier)
     * POST /api/v1/orders/{order}/accept
     */
    public function accept(string $orderId, Request $request): JsonResponse
    {
        $order = Order::find($orderId);

        if (!$order) {
            return $this->notFound('Commande non trouvée.');
        }

        if (!$request->user()->isCourier()) {
            return $this->forbidden('Réservé aux coursiers.');
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
     */
    public function updateStatus(string $orderId, UpdateOrderStatusRequest $request): JsonResponse
    {
        $order = Order::find($orderId);

        if (!$order) {
            return $this->notFound('Commande non trouvée.');
        }

        // Check authorization
        $user = $request->user();
        if ($order->courier_id !== $user->id && !$user->isAdmin()) {
            return $this->forbidden('Accès non autorisé.');
        }

        $newStatus = OrderStatus::from($request->status);

        $result = $this->orderService->updateStatus(
            $order,
            $newStatus,
            $user,
            $request->note ?? $request->cancellation_reason,
            $request->latitude,
            $request->longitude
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
    public function cancel(string $orderId, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $order = Order::find($orderId);

        if (!$order) {
            return $this->notFound('Commande non trouvée.');
        }

        // Only client or admin can cancel
        $user = $request->user();
        if ($order->client_id !== $user->id && !$user->isAdmin()) {
            return $this->forbidden('Accès non autorisé.');
        }

        // Can only cancel pending or assigned orders
        if (!in_array($order->status, [OrderStatus::PENDING, OrderStatus::ASSIGNED])) {
            return $this->error('Cette commande ne peut plus être annulée.');
        }

        $result = $this->orderService->updateStatus(
            $order,
            OrderStatus::CANCELLED,
            $user,
            $validated['reason']
        );

        if (!$result['success']) {
            return $this->error($result['message']);
        }

        return $this->success($result['order'], 'Commande annulée.');
    }

    /**
     * Rate courier (client)
     * POST /api/v1/orders/{order}/rate-courier
     */
    public function rateCourier(string $orderId, RateOrderRequest $request): JsonResponse
    {
        $order = Order::find($orderId);

        if (!$order) {
            return $this->notFound('Commande non trouvée.');
        }

        if ($order->client_id !== $request->user()->id) {
            return $this->forbidden('Accès non autorisé.');
        }

        if (!$order->isCompleted()) {
            return $this->error('La commande doit être livrée pour être notée.');
        }

        if ($order->courier_rating) {
            return $this->error('Vous avez déjà noté ce coursier.');
        }

        $order->rateCourier($request->rating, $request->review);

        return $this->success($order->fresh(), 'Merci pour votre évaluation.');
    }

    /**
     * Rate client (courier)
     * POST /api/v1/orders/{order}/rate-client
     */
    public function rateClient(string $orderId, RateOrderRequest $request): JsonResponse
    {
        $order = Order::find($orderId);

        if (!$order) {
            return $this->notFound('Commande non trouvée.');
        }

        if ($order->courier_id !== $request->user()->id) {
            return $this->forbidden('Accès non autorisé.');
        }

        if (!$order->isCompleted()) {
            return $this->error('La commande doit être livrée pour être notée.');
        }

        if ($order->client_rating) {
            return $this->error('Vous avez déjà noté ce client.');
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
    public function tracking(string $orderId, Request $request): JsonResponse
    {
        $order = Order::with([
            'courier:id,name,phone,profile_photo,current_latitude,current_longitude,location_updated_at,vehicle_type,vehicle_plate',
            'statusHistories'
        ])->find($orderId);

        if (!$order) {
            return $this->notFound('Commande non trouvée.');
        }

        // Vérifier l'autorisation
        $user = $request->user();
        if ($order->client_id !== $user->id && $order->courier_id !== $user->id && !$user->isAdmin()) {
            return $this->forbidden('Accès non autorisé.');
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
            $estimatedDistance = $this->calculateDistance(
                $order->courier->current_latitude,
                $order->courier->current_longitude,
                $destLat,
                $destLng
            );
            
            // Estimer le temps (moyenne 25 km/h en ville)
            $estimatedMinutes = (int) ceil(($estimatedDistance / 25) * 60);
            $estimatedArrival = now()->addMinutes($estimatedMinutes)->toIso8601String();
        }

        // Construire les événements de tracking
        $events = $order->statusHistories->map(function ($history) {
            return [
                'type' => $history->status,
                'title' => $this->getStatusLabel($history->status),
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
                'courier_photo' => $order->courier->profile_photo,
                'latitude' => $order->courier->current_latitude,
                'longitude' => $order->courier->current_longitude,
                'speed' => null, // TODO: Calculer depuis les positions précédentes
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
            'route_polyline' => [], // TODO: Ajouter le polyline de Google Directions
            'events' => $events,
        ]);
    }

    /**
     * Calculer la distance entre deux points GPS (en km)
     */
    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c, 2);
    }

    /**
     * Obtenir le label du statut
     */
    private function getStatusLabel(string $status): string
    {
        return match ($status) {
            'pending' => 'En attente',
            'assigned' => 'Coursier assigné',
            'picked_up' => 'Colis récupéré',
            'in_transit' => 'En cours de livraison',
            'delivered' => 'Livré',
            'cancelled' => 'Annulé',
            default => ucfirst($status),
        };
    }
}
