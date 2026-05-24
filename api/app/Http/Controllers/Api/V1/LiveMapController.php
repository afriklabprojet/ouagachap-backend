<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\User;
use App\Services\DelayPredictorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Carte Live (Admin)
 *
 * Endpoints temps réel pour la carte de supervision admin.
 */
class LiveMapController extends BaseController
{
    public function __construct(private readonly DelayPredictorService $etaService) {}

    /**
     * Livreurs actifs (positions)
     *
     * Retourne tous les livreurs avec position GPS mise à jour dans les 10 dernières minutes.
     *
     * @response 200 {"success":true,"message":"Success","data":[{"id":1,"name":"Moussa D.","lat":12.36,"lng":-1.53,"battery":78,"status":"busy","order_id":"uuid","updated_at":"2024-01-01T12:00:00+00:00"}]}
     */
    public function liveCouriers(Request $request): JsonResponse
    {
        $couriers = User::where('role', UserRole::COURIER)
            ->whereNotNull('current_latitude')
            ->whereNotNull('current_longitude')
            ->where('location_updated_at', '>=', now()->subMinutes(15))
            ->with([
                'orders' => fn($q) => $q
                    ->whereIn('status', [
                        OrderStatus::ASSIGNED,
                        OrderStatus::ACCEPTED,
                        OrderStatus::PICKING_UP,
                        OrderStatus::PICKED_UP,
                        OrderStatus::IN_TRANSIT,
                    ])
                    ->latest('assigned_at')
                    ->limit(1),
            ])
            ->get(['id', 'name', 'phone', 'current_latitude', 'current_longitude',
                   'battery_level', 'is_available', 'location_updated_at'])
            ->map(function (User $courier) {
                $activeOrder = $courier->orders->first();

                return [
                    'id'         => $courier->id,
                    'name'       => $courier->name,
                    'phone'      => $courier->phone,
                    'lat'        => (float) $courier->current_latitude,
                    'lng'        => (float) $courier->current_longitude,
                    'battery'    => $courier->battery_level,
                    'available'  => (bool) $courier->is_available,
                    'status'     => $activeOrder ? 'busy' : ($courier->is_available ? 'available' : 'offline'),
                    'order_id'   => $activeOrder?->id,
                    'updated_at' => $courier->location_updated_at?->toIso8601String(),
                    'freshness'  => $courier->location_updated_at
                        ? now()->diffInSeconds($courier->location_updated_at)
                        : null,
                ];
            });

        return $this->success($couriers);
    }

    /**
     * Commandes actives
     *
     * Retourne les commandes en cours avec positions pickup/dropoff, coursier assigné et ETA.
     */
    public function activeOrders(Request $request): JsonResponse
    {
        $activeStatuses = [
            OrderStatus::ASSIGNED,
            OrderStatus::ACCEPTED,
            OrderStatus::PICKING_UP,
            OrderStatus::PICKED_UP,
            OrderStatus::IN_TRANSIT,
        ];

        $orders = Order::whereIn('status', $activeStatuses)
            ->whereNotNull('pickup_latitude')
            ->whereNotNull('dropoff_latitude')
            ->with(['courier:id,name,current_latitude,current_longitude', 'client:id,name'])
            ->get([
                'id', 'status', 'courier_id', 'client_id',
                'pickup_latitude', 'pickup_longitude',
                'dropoff_latitude', 'dropoff_longitude',
                'assigned_at', 'picked_up_at', 'created_at',
            ])
            ->map(function (Order $order) {
                $eta = null;
                if ($order->courier) {
                    try {
                        $result = $this->etaService->predictETA($order, $order->courier);
                        $eta = $result['minutes'] ?? null;
                    } catch (\Throwable) {
                        // ETA non disponible, on continue sans
                    }
                }

                return [
                    'id'     => $order->id,
                    'status' => $order->status->value,
                    'status_label' => $order->status->label(),
                    'pickup' => [
                        'lat' => (float) $order->pickup_latitude,
                        'lng' => (float) $order->pickup_longitude,
                    ],
                    'dropoff' => [
                        'lat' => (float) $order->dropoff_latitude,
                        'lng' => (float) $order->dropoff_longitude,
                    ],
                    'courier' => $order->courier ? [
                        'id'   => $order->courier->id,
                        'name' => $order->courier->name,
                        'lat'  => (float) $order->courier->current_latitude,
                        'lng'  => (float) $order->courier->current_longitude,
                    ] : null,
                    'client_name' => $order->client?->name,
                    'eta_minutes' => $eta,
                    'age_minutes' => $order->assigned_at
                        ? now()->diffInMinutes($order->assigned_at)
                        : now()->diffInMinutes($order->created_at),
                ];
            });

        return $this->success($orders);
    }

    /**
     * Données heatmap
     *
     * Retourne les points de densité des commandes des dernières 24h pour la heatmap.
     * Format: [[lat, lng, intensity], ...]
     */
    public function heatmapData(Request $request): JsonResponse
    {
        $hours = (int) $request->query('hours', 24);
        $hours = min(max($hours, 1), 168); // entre 1h et 7 jours

        // Points pickup des commandes récentes
        $pickupPoints = Order::where('created_at', '>=', now()->subHours($hours))
            ->whereNotNull('pickup_latitude')
            ->whereNotNull('pickup_longitude')
            ->get(['pickup_latitude', 'pickup_longitude'])
            ->map(fn(Order $o) => [
                (float) $o->pickup_latitude,
                (float) $o->pickup_longitude,
                0.6, // intensité pickup
            ]);

        // Points dropoff (zones de livraison)
        $dropoffPoints = Order::where('created_at', '>=', now()->subHours($hours))
            ->whereNotNull('dropoff_latitude')
            ->whereNotNull('dropoff_longitude')
            ->get(['dropoff_latitude', 'dropoff_longitude'])
            ->map(fn(Order $o) => [
                (float) $o->dropoff_latitude,
                (float) $o->dropoff_longitude,
                0.4,
            ]);

        $points = $pickupPoints->merge($dropoffPoints)->values();

        return $this->success([
            'points'  => $points,
            'hours'   => $hours,
            'total'   => $points->count(),
        ]);
    }

    /**
     * Stats globales carte
     *
     * Métriques en temps réel pour les compteurs de la carte.
     */
    public function mapStats(Request $request): JsonResponse
    {
        $activeStatuses = [
            OrderStatus::ASSIGNED,
            OrderStatus::ACCEPTED,
            OrderStatus::PICKING_UP,
            OrderStatus::PICKED_UP,
            OrderStatus::IN_TRANSIT,
        ];

        $totalCouriers = User::where('role', UserRole::COURIER)
            ->whereNotNull('current_latitude')
            ->where('location_updated_at', '>=', now()->subMinutes(15))
            ->count();

        $availableCouriers = User::where('role', UserRole::COURIER)
            ->where('is_available', true)
            ->whereNotNull('current_latitude')
            ->where('location_updated_at', '>=', now()->subMinutes(15))
            ->count();

        $activeOrdersCount = Order::whereIn('status', $activeStatuses)->count();
        $pendingOrdersCount = Order::where('status', OrderStatus::PENDING)->count();
        $deliveredToday = Order::where('status', OrderStatus::DELIVERED)
            ->whereDate('delivered_at', today())
            ->count();

        return $this->success([
            'couriers' => [
                'total'     => $totalCouriers,
                'available' => $availableCouriers,
                'busy'      => $totalCouriers - $availableCouriers,
            ],
            'orders' => [
                'active'    => $activeOrdersCount,
                'pending'   => $pendingOrdersCount,
                'delivered_today' => $deliveredToday,
            ],
            'generated_at' => now()->toIso8601String(),
        ]);
    }
}
