<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\GeofenceAlert;
use App\Models\Zone;
use App\Services\GeofenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GeofenceController extends Controller
{
    public function __construct(
        private GeofenceService $geofenceService
    ) {}

    /**
     * Mettre à jour la position et vérifier les géofences (pour coursiers)
     */
    public function updatePosition(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'order_id' => 'nullable|uuid|exists:orders,id',
        ]);

        $courier = $request->user();

        // Mettre à jour la position du coursier
        $courier->update([
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'last_location_at' => now(),
        ]);

        // Si une commande est spécifiée, vérifier les géofences
        $alerts = [];
        if ($validated['order_id'] ?? null) {
            $order = \App\Models\Order::find($validated['order_id']);
            
            if ($order && $order->courier_id === $courier->id) {
                $alerts = $this->geofenceService->checkCourierPosition(
                    $courier,
                    $order,
                    $validated['latitude'],
                    $validated['longitude']
                );
            }
        }

        // Vérifier dans quelle zone se trouve le coursier
        $currentZone = $this->findZoneForPosition($validated['latitude'], $validated['longitude']);

        return response()->json([
            'success' => true,
            'data' => [
                'position_updated' => true,
                'current_zone' => $currentZone ? [
                    'id' => $currentZone->id,
                    'name' => $currentZone->name,
                ] : null,
                'alerts' => $alerts,
            ],
        ]);
    }

    /**
     * Obtenir les alertes géofence pour une commande
     */
    public function orderAlerts(Request $request, string $orderId): JsonResponse
    {
        $order = \App\Models\Order::findOrFail($orderId);

        // Vérifier que l'utilisateur a accès à cette commande
        $user = $request->user();
        if ($order->client_id !== $user->id && $order->courier_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé.',
            ], 403);
        }

        $alerts = GeofenceAlert::where('order_id', $orderId)
            ->with('zone:id,name')
            ->orderByDesc('triggered_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $alerts,
        ]);
    }

    /**
     * Calculer le multiplicateur de prix dynamique pour une zone
     */
    public function dynamicPricing(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'zone_id' => 'required|integer|exists:zones,id',
        ]);

        $zone = Zone::findOrFail($validated['zone_id']);
        $multiplier = $this->geofenceService->calculateDynamicMultiplier($zone);

        return response()->json([
            'success' => true,
            'data' => [
                'zone_id' => $zone->id,
                'zone_name' => $zone->name,
                'base_price' => $zone->base_price,
                'multiplier' => $multiplier,
                'adjusted_price' => round($zone->base_price * $multiplier),
                'reason' => $this->getPricingReason($multiplier),
            ],
        ]);
    }

    /**
     * Obtenir les zones avec tarification dynamique actuelle
     */
    public function zonesWithPricing(Request $request): JsonResponse
    {
        $zones = Zone::where('is_active', true)->get();

        $zonesData = $zones->map(function ($zone) {
            $multiplier = $this->geofenceService->calculateDynamicMultiplier($zone);
            return [
                'id' => $zone->id,
                'name' => $zone->name,
                'base_price' => $zone->base_price,
                'price_per_km' => $zone->price_per_km,
                'multiplier' => $multiplier,
                'adjusted_base_price' => round($zone->base_price * $multiplier),
                'is_surge' => $multiplier > 1.0,
                'surge_reason' => $multiplier > 1.0 ? $this->getPricingReason($multiplier) : null,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $zonesData,
        ]);
    }

    /**
     * Vérifier si une position est dans une zone spécifique
     */
    public function checkPosition(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'zone_id' => 'nullable|integer|exists:zones,id',
        ]);

        $zone = null;

        if ($validated['zone_id'] ?? null) {
            $zone = Zone::find($validated['zone_id']);
            $isInZone = $this->isPositionInZone(
                $validated['latitude'],
                $validated['longitude'],
                $zone
            );
        } else {
            // Trouver la zone automatiquement
            $zone = $this->findZoneForPosition($validated['latitude'], $validated['longitude']);
            $isInZone = $zone !== null;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'is_in_zone' => $isInZone,
                'zone' => $zone ? [
                    'id' => $zone->id,
                    'name' => $zone->name,
                    'base_price' => $zone->base_price,
                ] : null,
                'position' => [
                    'latitude' => $validated['latitude'],
                    'longitude' => $validated['longitude'],
                ],
            ],
        ]);
    }

    /**
     * Historique des alertes géofence pour le coursier connecté
     */
    public function myAlerts(Request $request): JsonResponse
    {
        $alerts = GeofenceAlert::where('courier_id', $request->user()->id)
            ->with(['zone:id,name', 'order:id,tracking_number'])
            ->orderByDesc('triggered_at')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $alerts,
        ]);
    }

    /**
     * Trouver la zone pour une position donnée
     */
    private function findZoneForPosition(float $lat, float $lng): ?Zone
    {
        $zones = Zone::where('is_active', true)->get();

        foreach ($zones as $zone) {
            if ($this->isPositionInZone($lat, $lng, $zone)) {
                return $zone;
            }
        }

        return null;
    }

    /**
     * Vérifier si une position est dans une zone (par rayon)
     */
    private function isPositionInZone(float $lat, float $lng, Zone $zone): bool
    {
        if (!$zone->center_lat || !$zone->center_lng || !$zone->radius_km) {
            return false;
        }

        $distance = $this->geofenceService->calculateDistance(
            $lat,
            $lng,
            $zone->center_lat,
            $zone->center_lng
        );

        return $distance <= $zone->radius_km;
    }

    /**
     * Obtenir la raison du multiplicateur de prix
     */
    private function getPricingReason(float $multiplier): string
    {
        if ($multiplier >= 2.0) {
            return 'Très forte demande - Heures de pointe';
        }
        if ($multiplier >= 1.5) {
            return 'Forte demande - Peu de coursiers disponibles';
        }
        if ($multiplier > 1.0) {
            return 'Demande modérée';
        }
        if ($multiplier < 1.0) {
            return 'Tarif réduit - Faible demande';
        }
        return 'Tarif normal';
    }
}
