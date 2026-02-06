<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\OrderStatus;
use App\Events\CourierWentOnline;
use App\Http\Requests\Courier\UpdateAvailabilityRequest;
use App\Http\Requests\Courier\UpdateLocationRequest;
use App\Models\Order;
use App\Services\CourierService;
use App\Services\OrderService;
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
        private OrderService $orderService
    ) {}

    /**
     * Mettre à jour la position
     *
     * Met à jour la position GPS du coursier en temps réel.
     * À appeler régulièrement pendant les livraisons.
     *
     * @bodyParam latitude number required Latitude GPS. Example: 12.371400
     * @bodyParam longitude number required Longitude GPS. Example: -1.519700
     * @response 200 {"success": true, "message": "Position mise à jour.", "data": {"latitude": 12.3714, "longitude": -1.5197}}
     * @response 403 {"success": false, "message": "Réservé aux coursiers."}
     */
    public function updateLocation(UpdateLocationRequest $request): JsonResponse
    {
        if (!$request->user()->isCourier()) {
            return $this->forbidden('Réservé aux coursiers.');
        }

        $result = $this->courierService->updateLocation(
            $request->user(),
            $request->latitude,
            $request->longitude
        );

        return $this->success($result, $result['message']);
    }

    /**
     * Modifier la disponibilité
     *
     * Active ou désactive la disponibilité du coursier pour recevoir des commandes.
     *
     * @bodyParam is_available boolean required Disponibilité. Example: true
     * @response 200 {"success": true, "message": "Disponibilité mise à jour.", "data": {"is_available": true}}
     * @response 403 {"success": false, "message": "Réservé aux coursiers."}
     */
    public function updateAvailability(UpdateAvailabilityRequest $request): JsonResponse
    {
        if (!$request->user()->isCourier()) {
            return $this->forbidden('Réservé aux coursiers.');
        }

        $result = $this->courierService->updateAvailability(
            $request->user(),
            $request->is_available
        );

        if (!$result['success']) {
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
     * @response 200 {"success": true, "message": "Statut mis à jour.", "data": {"is_online": true}}
     */
    public function updateOnlineStatus(Request $request): JsonResponse
    {
        if (!$request->user()->isCourier()) {
            return $this->forbidden('Réservé aux coursiers.');
        }

        $request->validate([
            'is_online' => 'required|boolean',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $user = $request->user();
        $wasOnline = $user->is_available;
        $isNowOnline = $request->is_online;
        
        // Mettre à jour la disponibilité
        $user->is_available = $isNowOnline;
        
        // Mettre à jour la position si fournie
        if ($request->has('latitude') && $request->has('longitude')) {
            $user->current_latitude = $request->latitude;
            $user->current_longitude = $request->longitude;
        }
        
        $user->last_seen_at = now();
        $user->save();

        // Notifier les admins si le statut a changé
        if ($wasOnline !== $isNowOnline) {
            event(new CourierWentOnline($user, $isNowOnline ? 'online' : 'offline'));
        }

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
        if (!$request->user()->isCourier()) {
            return $this->forbidden('Réservé aux coursiers.');
        }

        $stats = $this->courierService->getCourierStats($request->user());

        return $this->success($stats);
    }

    /**
     * Get courier's orders
     * GET /api/v1/courier/orders
     */
    public function orders(Request $request): JsonResponse
    {
        if (!$request->user()->isCourier()) {
            return $this->forbidden('Réservé aux coursiers.');
        }

        $status = $request->query('status');
        $perPage = $request->query('per_page', 15);

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
        if (!$request->user()->isCourier()) {
            return $this->forbidden('Réservé aux coursiers.');
        }

        $order = $request->user()->courierOrders()
            ->with(['client:id,name,phone'])
            ->inProgress()
            ->first();

        if (!$order) {
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
        if (!$request->user()->isCourier()) {
            return $this->forbidden('Réservé aux coursiers.');
        }

        $perPage = $request->query('per_page', 15);
        $earnings = $this->courierService->getEarningsHistory($request->user(), $perPage);

        return $this->paginated($earnings, 'Historique des gains.');
    }

    /**
     * Get available orders near courier location
     * GET /api/v1/courier/available-orders
     */
    public function availableOrders(Request $request): JsonResponse
    {
        if (!$request->user()->isCourier()) {
            return $this->forbidden('Réservé aux coursiers.');
        }

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
    public function showOrder(Request $request, $orderId): JsonResponse
    {
        if (!$request->user()->isCourier()) {
            return $this->forbidden('Réservé aux coursiers.');
        }

        $order = \App\Models\Order::with(['client:id,name,phone'])
            ->where('courier_id', $request->user()->id)
            ->where('id', $orderId)
            ->first();

        if (!$order) {
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
        if (!$request->user()->isCourier()) {
            return $this->forbidden('Réservé aux coursiers.');
        }

        $order = $request->user()->courierOrders()
            ->with(['client:id,name,phone'])
            ->whereIn('status', ['assigned', 'accepted', 'picking_up', 'picked_up', 'in_transit'])
            ->first();

        return $this->success($order, $order ? 'Livraison en cours.' : 'Aucune livraison active.');
    }

    /**
     * Get delivery history for courier
     * GET /api/v1/courier/delivery-history
     */
    public function deliveryHistory(Request $request): JsonResponse
    {
        if (!$request->user()->isCourier()) {
            return $this->forbidden('Réservé aux coursiers.');
        }

        $orders = $request->user()->courierOrders()
            ->with(['client:id,name,phone'])
            ->whereIn('status', ['delivered', 'cancelled'])
            ->orderBy('updated_at', 'desc')
            ->paginate(20);

        return $this->success($orders, 'Historique des livraisons.');
    }

    /**
     * Accept an order
     * POST /api/v1/courier/orders/{order}/accept
     */
    public function acceptOrder(Request $request, $orderId): JsonResponse
    {
        if (!$request->user()->isCourier()) {
            return $this->forbidden('Réservé aux coursiers.');
        }

        $result = $this->orderService->assignCourier(
            $orderId,
            $request->user()->id
        );

        if (!$result['success']) {
            return $this->error($result['message']);
        }

        return $this->success($result['order'], $result['message']);
    }

    /**
     * Update order status (picked_up, delivered)
     * PUT /api/v1/courier/orders/{order}/status
     */
    public function updateOrderStatus(Request $request, $orderId): JsonResponse
    {
        if (!$request->user()->isCourier()) {
            return $this->forbidden('Réservé aux coursiers.');
        }

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:picking_up,picked_up,in_transit,delivered'],
        ]);

        // Find the order by UUID
        $order = Order::where('id', $orderId)
            ->where('courier_id', $request->user()->id)
            ->first();

        if (!$order) {
            return $this->notFound('Commande non trouvée ou non assignée à vous.');
        }

        // Convert string status to OrderStatus enum
        $newStatus = OrderStatus::from($validated['status']);

        $result = $this->orderService->updateStatus(
            $order,
            $newStatus,
            $request->user()
        );

        if (!$result['success']) {
            return $this->error($result['message']);
        }

        return $this->success($result['order'], $result['message']);
    }

    /**
     * Confirm delivery with client's confirmation code
     * POST /api/v1/courier/orders/{order}/confirm-delivery
     */
    public function confirmDelivery(Request $request, $orderId): JsonResponse
    {
        if (!$request->user()->isCourier()) {
            return $this->forbidden('Réservé aux coursiers.');
        }

        $validated = $request->validate([
            'confirmation_code' => ['required', 'string', 'size:4'],
        ]);

        // Find the order by UUID
        $order = Order::where('id', $orderId)
            ->where('courier_id', $request->user()->id)
            ->first();

        if (!$order) {
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

        if (!$result['success']) {
            return $this->error($result['message']);
        }

        // Mark recipient as confirmed
        $order->update(['recipient_confirmed' => true]);

        return $this->success($result['order'], 'Livraison confirmée avec succès !');
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

        return $this->success($couriers, 'Coursiers disponibles.');
    }
}
