<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\OrderStatus;
use App\Http\Requests\Courier\ConfirmDeliveryRequest;
use App\Http\Requests\Courier\UpdateAvailabilityRequest;
use App\Http\Requests\Courier\UpdateLocationRequest;
use App\Http\Requests\Courier\UpdateOnlineStatusRequest;
use App\Http\Requests\Courier\UpdateOrderStatusRequest;
use App\Models\Order;
use App\Services\CdnService;
use App\Services\CourierService;
use App\Services\OrderService;
use App\Services\RouteOptimizerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Coursiers
 *
 * Endpoints réservés aux coursiers pour gérer leur disponibilité, localisation et commandes.
 */
class CourierController extends BaseController
{
    public function __construct(
        private CourierService $courierService,
        private OrderService $orderService,
        private RouteOptimizerService $routeOptimizerService,
    ) {}

    /**
     * Mettre à jour la position
     *
     * Met à jour la position GPS du coursier en temps réel.
     * À appeler régulièrement pendant les livraisons.
     *
     * @bodyParam latitude number required Latitude GPS. Example: 12.371400
     * @bodyParam longitude number required Longitude GPS. Example: -1.519700
     *
     * @response 200 {"success": true, "message": "Position mise à jour.", "data": {"latitude": 12.3714, "longitude": -1.5197}}
     */
    public function updateLocation(UpdateLocationRequest $request): JsonResponse
    {
        $result = $this->courierService->updateLocation(
            courier: $request->user(),
            latitude: (float) $request->latitude,
            longitude: (float) $request->longitude,
            heading: $request->heading !== null ? (float) $request->heading : null,
            speed: $request->speed !== null ? (float) $request->speed : null,
            accuracy: $request->accuracy !== null ? (float) $request->accuracy : null,
        );

        return $this->success($result, $result['message']);
    }

    /**
     * Modifier la disponibilité
     *
     * Active ou désactive la disponibilité du coursier pour recevoir des commandes.
     *
     * @bodyParam is_available boolean required Disponibilité. Example: true
     *
     * @response 200 {"success": true, "message": "Disponibilité mise à jour.", "data": {"is_available": true}}
     */
    public function updateAvailability(UpdateAvailabilityRequest $request): JsonResponse
    {
        $result = $this->courierService->updateAvailability(
            $request->user(),
            $request->is_available
        );

        if (! $result['success']) {
            return $this->error($result['message']);
        }

        return $this->success($result, $result['message']);
    }

    /**
     * Mettre à jour le statut en ligne
     *
     * Active ou désactive le mode en ligne du coursier (avec position GPS).
     *
     * @bodyParam is_online boolean required Statut en ligne. Example: true
     * @bodyParam latitude number Latitude GPS actuelle. Example: 12.371400
     * @bodyParam longitude number Longitude GPS actuelle. Example: -1.519700
     *
     * @response 200 {"success": true, "message": "Statut mis à jour.", "data": {"is_online": true}}
     */
    public function updateOnlineStatus(UpdateOnlineStatusRequest $request): JsonResponse
    {
        $user = $request->user();
        $isNowOnline = $request->boolean('is_online');

        $availabilityResult = $this->courierService->updateAvailability($user, $isNowOnline);
        if (! $availabilityResult['success']) {
            return $this->error($availabilityResult['message'], 400, $availabilityResult);
        }

        // Mettre à jour la position si fournie
        if ($request->has('latitude') && $request->has('longitude')) {
            $user->current_latitude = $request->latitude;
            $user->current_longitude = $request->longitude;
        }

        $user->last_seen_at = now();
        $user->save();

        return $this->success([
            'is_online' => $user->is_available,
            'latitude' => $user->current_latitude,
            'longitude' => $user->current_longitude,
        ], $isNowOnline ? 'Vous êtes en ligne.' : 'Vous êtes hors ligne.');
    }

    /**
     * Get courier dashboard/stats
     * GET /api/v1/courier/dashboard
     */
    public function dashboard(Request $request): JsonResponse
    {
        $stats = $this->courierService->getCourierStats($request->user());

        return $this->success($stats);
    }

    /**
     * Get courier's orders
     * GET /api/v1/courier/orders
     */
    public function orders(Request $request): JsonResponse
    {
        $status = $request->query('status');
        $perPage = min((int) $request->query('per_page', 15), 100);

        $orders = $this->orderService->getCourierOrders(
            $request->user(),
            $status,
            $perPage
        );

        return $this->paginated($orders, 'Commandes récupérées.');
    }

    /**
     * Get current active order
     * GET /api/v1/courier/current-order
     */
    public function currentOrder(Request $request): JsonResponse
    {
        $order = $request->user()->courierOrders()
            ->with(['client:id,name,phone', 'zone:id,name'])
            ->inProgress()
            ->first();

        if (! $order) {
            return $this->success(null, 'Aucune commande en cours.');
        }

        return $this->success($order);
    }

    /**
     * Get earnings history
     * GET /api/v1/courier/earnings
     */
    public function earnings(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 15), 100);
        $earnings = $this->courierService->getEarningsHistory($request->user(), $perPage);

        return $this->paginated($earnings, 'Historique des gains.');
    }

    /**
     * Get available orders near courier location
     * GET /api/v1/courier/available-orders
     */
    public function availableOrders(Request $request): JsonResponse
    {
        $latitude = $request->query('latitude', $request->user()->current_latitude);
        $longitude = $request->query('longitude', $request->user()->current_longitude);
        $radius = $request->query('radius', 10); // 10km par défaut

        $orders = $this->orderService->getAvailableOrdersForCourier(
            $latitude,
            $longitude,
            $radius
        );

        return $this->success($orders, 'Commandes disponibles.');
    }

    /**
     * Get order details for courier
     * GET /api/v1/courier/orders/{order}
     */
    public function showOrder(Request $request, string $orderId): JsonResponse
    {
        $order = \App\Models\Order::with(['client:id,name,phone', 'zone:id,name'])
            ->where('courier_id', $request->user()->id)
            ->where('id', $orderId)
            ->first();

        if (! $order) {
            return $this->notFound('Commande non trouvée.');
        }

        return $this->success($order, 'Détails de la commande.');
    }

    /**
     * Get current active delivery for courier
     * GET /api/v1/courier/active-delivery
     */
    public function activeDelivery(Request $request): JsonResponse
    {
        $order = $request->user()->courierOrders()
            ->with(['client:id,name,phone', 'zone:id,name'])
            ->whereIn('status', OrderStatus::activeStatuses())
            ->first();

        return $this->success($order, $order ? 'Livraison en cours.' : 'Aucune livraison active.');
    }

    /**
     * Get delivery history for courier
     * GET /api/v1/courier/delivery-history
     */
    public function deliveryHistory(Request $request): JsonResponse
    {
        $orders = $request->user()->courierOrders()
            ->with(['client:id,name,phone', 'zone:id,name'])
            ->whereIn('status', ['delivered', 'cancelled'])
            ->orderBy('updated_at', 'desc')
            ->paginate(20);

        return $this->paginated($orders, 'Historique des livraisons.');
    }

    /**
     * Accept an order
     * POST /api/v1/courier/orders/{order}/accept
     */
    public function acceptOrder(Request $request, string $orderId): JsonResponse
    {
        $result = $this->orderService->assignCourier(
            $orderId,
            $request->user()->id
        );

        if (! $result['success']) {
            $errors = isset($result['error_code'])
                ? ['code' => $result['error_code']]
                : null;

            return $this->error($result['message'], 422, $errors);
        }

        return $this->success($result['order'], $result['message']);
    }

    /**
     * Update order status (picked_up, delivered)
     * PUT /api/v1/courier/orders/{order}/status
     */
    public function updateOrderStatus(UpdateOrderStatusRequest $request, string $orderId): JsonResponse
    {
        $validated = $request->validated();

        // Find the order by UUID
        $order = Order::where('id', $orderId)
            ->where('courier_id', $request->user()->id)
            ->first();

        if (! $order) {
            return $this->notFound('Commande non trouvée ou non assignée à vous.');
        }

        // Convert string status to OrderStatus enum
        $newStatus = OrderStatus::from($validated['status']);

        $result = $this->orderService->updateStatus(
            $order,
            $newStatus,
            $request->user()
        );

        if (! $result['success']) {
            return $this->error($result['message'], 422);
        }

        return $this->success($result['order'], $result['message']);
    }

    /**
     * Confirm delivery with client's confirmation code
     * POST /api/v1/courier/orders/{order}/confirm-delivery
     */
    public function confirmDelivery(ConfirmDeliveryRequest $request, string $orderId): JsonResponse
    {
        $validated = $request->validated();

        // Find the order by UUID
        $order = Order::where('id', $orderId)
            ->where('courier_id', $request->user()->id)
            ->first();

        if (! $order) {
            return $this->notFound('Commande non trouvée ou non assignée à vous.');
        }

        // Verify the confirmation code
        if ($order->recipient_confirmation_code !== $validated['confirmation_code']) {
            return $this->error('Code de confirmation incorrect. Demandez le bon code au client.', 422);
        }

        // Update status to delivered
        $result = $this->orderService->updateStatus(
            $order,
            OrderStatus::DELIVERED,
            $request->user()
        );

        if ($result['success']) {
            $updates = ['recipient_confirmed' => true];

            if ($request->hasFile('photo')) {
                $updates['delivery_photo_url'] = app(CdnService::class)
                    ->upload($request->file('photo'), 'delivery-photos/' . now()->format('Y/m'));
            }

            $order->update($updates);
        }

        return $result['success']
            ? $this->success($result['order']->fresh(), 'Livraison confirmée avec succès !')
            : $this->error($result['message']);
    }

    /**
     * Get nearby available couriers (for admin/system)
     * GET /api/v1/couriers/nearby
     */
    public function nearby(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
            'radius' => ['sometimes', 'numeric', 'min:1', 'max:50'],
        ]);

        $couriers = $this->courierService->getAvailableCouriers(
            $validated['latitude'],
            $validated['longitude'],
            $validated['radius'] ?? 5
        );

        // @codeCoverageIgnoreStart
        return $this->success($couriers, 'Coursiers disponibles.');
        // @codeCoverageIgnoreEnd
    }

    /**
     * Profil public d'un coursier
     *
     * Retourne les informations publiques d'un coursier (nom, photo, note, véhicule).
     * Accessible par les clients authentifiés.
     *
     * @urlParam courier int required L'ID du coursier.
     *
     * @response 200 {"success": true, "data": {"id": 1, "name": "Ali", "avatar_url": "https://...", ...}}
     */
    public function publicProfile(int $courier): JsonResponse
    {
        $user = \App\Models\User::where('id', $courier)
            ->where('role', \App\Enums\UserRole::COURIER)
            ->select([
                'id', 'name', 'avatar', 'vehicle_type', 'vehicle_plate',
                'vehicle_model', 'average_rating', 'total_ratings', 'total_orders',
                'created_at',
            ])
            ->first();

        if (! $user) {
            return $this->notFound('Coursier introuvable.');
        }

        return $this->success([
            'id' => $user->id,
            'name' => $user->name,
            'avatar_url' => $user->avatar_url,
            'vehicle_type' => $user->vehicle_type,
            'vehicle_plate' => $user->vehicle_plate,
            'vehicle_model' => $user->vehicle_model,
            'average_rating' => (float) $user->average_rating,
            'total_ratings' => $user->total_ratings,
            'total_orders' => $user->total_orders,
            'member_since' => $user->created_at?->format('Y-m-d'),
        ]);
    }

    /**
     * Cancel an order (courier)
     * POST /api/v1/courier/orders/{order}/cancel
     */
    public function cancelOrder(Request $request, string $orderId): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $order = Order::where('id', $orderId)
            ->where('courier_id', $request->user()->id)
            ->whereIn('status', [
                OrderStatus::ASSIGNED->value,
                OrderStatus::ACCEPTED->value,
                OrderStatus::PICKING_UP->value,
            ])
            ->first();

        if (! $order) {
            return $this->notFound('Commande non trouvée ou annulation impossible à ce stade.');
        }

        $result = $this->orderService->updateStatus(
            $order,
            OrderStatus::CANCELLED,
            $request->user(),
            note: $validated['reason']
        );

        if (! $result['success']) {
            return $this->error($result['message'], 422);
        }

        return $this->success($result['order'], 'Commande annulée.');
    }

    // ==================== SMART DISPATCH ====================

    /**
     * Mettre à jour le niveau de batterie
     *
     * Permet au coursier de signaler son niveau de batterie pour éviter les assignations
     * de longue distance quand la batterie est faible.
     *
     * @bodyParam battery_level integer required Niveau de batterie (0-100). Example: 45
     *
     * @response 200 {"success": true, "message": "Niveau de batterie mis à jour.", "data": {"battery_level": 45}}
     */
    public function updateBattery(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'battery_level' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $courier = $request->user();
        $courier->update([
            'battery_level' => $validated['battery_level'],
            'battery_updated_at' => now(),
        ]);

        return $this->success(
            ['battery_level' => $validated['battery_level']],
            'Niveau de batterie mis à jour.'
        );
    }

    /**
     * Plan d'itinéraire optimisé
     *
     * Retourne l'ordre optimisé de livraison pour les commandes actives du coursier
     * en utilisant un algorithme Nearest-Neighbor TSP.
     *
     * @response 200 {"success": true, "message": "Itinéraire calculé.", "data": {"total_distance_km": 4.2, "total_eta_minutes": 28, "stops": [], "order_count": 2}}
     */
    public function routePlan(Request $request): JsonResponse
    {
        $courier = $request->user();
        $plan = $this->routeOptimizerService->optimizeRoute($courier);

        return $this->success($plan, 'Itinéraire calculé.');
    }
}
