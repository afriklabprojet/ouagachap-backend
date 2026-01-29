<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Services\PushNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Contrôleur pour les colis entrants (colis que l'utilisateur va RECEVOIR)
 */
class IncomingOrderController extends Controller
{
    public function __construct(
        private PushNotificationService $pushService
    ) {}

    /**
     * Liste des colis entrants pour l'utilisateur connecté
     * GET /api/v1/incoming-orders
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $status = $request->query('status');
        
        $query = Order::where('recipient_user_id', $user->id)
            ->with(['client:id,name,phone', 'courier:id,name,phone,current_latitude,current_longitude'])
            ->orderByDesc('created_at');
        
        // Filtrer par statut si spécifié
        if ($status) {
            $query->where('status', $status);
        }
        
        $orders = $query->paginate(20);
        
        return response()->json([
            'success' => true,
            'message' => 'Colis entrants récupérés',
            'data' => [
                'orders' => $orders->items(),
                'pagination' => [
                    'current_page' => $orders->currentPage(),
                    'last_page' => $orders->lastPage(),
                    'per_page' => $orders->perPage(),
                    'total' => $orders->total(),
                ],
                'stats' => $this->getIncomingStats($user),
            ],
        ]);
    }

    /**
     * Statistiques des colis entrants
     */
    private function getIncomingStats(User $user): array
    {
        $baseQuery = Order::where('recipient_user_id', $user->id);
        
        return [
            'pending' => (clone $baseQuery)->where('status', OrderStatus::PENDING)->count(),
            'in_transit' => (clone $baseQuery)->whereIn('status', [
                OrderStatus::ASSIGNED,
                OrderStatus::PICKED_UP,
            ])->count(),
            'delivered' => (clone $baseQuery)->where('status', OrderStatus::DELIVERED)->count(),
            'total' => (clone $baseQuery)->count(),
        ];
    }

    /**
     * Détails d'un colis entrant
     * GET /api/v1/incoming-orders/{id}
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        
        $order = Order::where('recipient_user_id', $user->id)
            ->where('id', $id)
            ->with([
                'client:id,name,phone',
                'courier:id,name,phone,current_latitude,current_longitude,vehicle_type',
                'statusHistories',
            ])
            ->first();
        
        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Colis non trouvé',
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Détails du colis',
            'data' => [
                'order' => $order,
                'sender' => [
                    'name' => $order->pickup_contact_name,
                    'phone' => $this->maskPhone($order->pickup_contact_phone),
                ],
                'can_track' => in_array($order->status, [
                    OrderStatus::ASSIGNED,
                    OrderStatus::PICKED_UP,
                ]),
                'needs_confirmation' => $order->status === OrderStatus::PICKED_UP && !$order->recipient_confirmed,
            ],
        ]);
    }

    /**
     * Suivre un colis en temps réel (position du coursier)
     * GET /api/v1/incoming-orders/{id}/track
     */
    public function track(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        
        $order = Order::where('recipient_user_id', $user->id)
            ->where('id', $id)
            ->whereIn('status', [OrderStatus::ASSIGNED, OrderStatus::PICKED_UP])
            ->with(['courier:id,name,phone,current_latitude,current_longitude,vehicle_type'])
            ->first();
        
        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Colis non trouvable ou déjà livré',
            ], 404);
        }
        
        if (!$order->courier) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun coursier assigné',
            ], 400);
        }
        
        // Calculer l'ETA approximatif (en minutes)
        $eta = null;
        if ($order->courier->current_latitude && $order->courier->current_longitude) {
            $distance = $this->calculateDistance(
                $order->courier->current_latitude,
                $order->courier->current_longitude,
                $order->dropoff_latitude,
                $order->dropoff_longitude
            );
            // Vitesse moyenne 25 km/h en ville
            $eta = ceil(($distance / 25) * 60);
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'status_label' => $order->status->label(),
                'courier' => [
                    'name' => $order->courier->name,
                    'phone' => $order->courier->phone,
                    'vehicle_type' => $order->courier->vehicle_type,
                    'latitude' => $order->courier->current_latitude,
                    'longitude' => $order->courier->current_longitude,
                ],
                'destination' => [
                    'address' => $order->dropoff_address,
                    'latitude' => $order->dropoff_latitude,
                    'longitude' => $order->dropoff_longitude,
                ],
                'eta_minutes' => $eta,
                'eta_text' => $eta ? "~{$eta} min" : 'Calcul en cours...',
            ],
        ]);
    }

    /**
     * Confirmer la réception d'un colis avec le code
     * POST /api/v1/incoming-orders/{id}/confirm
     */
    public function confirmReceipt(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'confirmation_code' => 'required|string|size:6',
        ]);
        
        $user = $request->user();
        
        $order = Order::where('recipient_user_id', $user->id)
            ->where('id', $id)
            ->where('status', OrderStatus::PICKED_UP)
            ->first();
        
        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Colis non trouvé ou pas en cours de livraison',
            ], 404);
        }
        
        // Vérifier le code de confirmation
        if ($order->recipient_confirmation_code !== $request->confirmation_code) {
            return response()->json([
                'success' => false,
                'message' => 'Code de confirmation incorrect',
            ], 400);
        }
        
        DB::transaction(function () use ($order) {
            $order->update([
                'recipient_confirmed' => true,
            ]);
            
            // Notifier le coursier que le destinataire attend
            if ($order->courier) {
                $this->pushService->sendToUser(
                    $order->courier,
                    '📍 Destinataire prêt',
                    "{$order->dropoff_contact_name} a confirmé être prêt à recevoir le colis",
                    ['order_id' => $order->id, 'type' => 'recipient_ready']
                );
            }
        });
        
        return response()->json([
            'success' => true,
            'message' => 'Réception confirmée ! Le coursier a été notifié.',
        ]);
    }

    /**
     * Recherche de colis par numéro de commande (pour les non-inscrits)
     * POST /api/v1/incoming-orders/search
     */
    public function searchByOrderNumber(Request $request): JsonResponse
    {
        $request->validate([
            'order_number' => 'required|string',
            'phone' => 'required|string', // Le téléphone du destinataire pour vérification
        ]);
        
        // Normaliser le numéro de téléphone
        $phone = preg_replace('/[^0-9+]/', '', $request->phone);
        if (!str_starts_with($phone, '+')) {
            $phone = '+226' . ltrim($phone, '0');
        }
        
        $order = Order::where('order_number', $request->order_number)
            ->where('dropoff_contact_phone', $phone)
            ->with(['courier:id,name,phone,current_latitude,current_longitude'])
            ->first();
        
        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Colis non trouvé. Vérifiez le numéro de commande et votre téléphone.',
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Colis trouvé',
            'data' => [
                'order_number' => $order->order_number,
                'status' => $order->status,
                'status_label' => $order->status->label(),
                'package_description' => $order->package_description,
                'sender_name' => $order->pickup_contact_name,
                'dropoff_address' => $order->dropoff_address,
                'courier' => $order->courier ? [
                    'name' => $order->courier->name,
                    'phone' => $order->courier->phone,
                ] : null,
                'created_at' => $order->created_at,
                'can_track' => in_array($order->status, [
                    OrderStatus::ASSIGNED,
                    OrderStatus::PICKED_UP,
                ]),
            ],
        ]);
    }

    /**
     * Masquer partiellement un numéro de téléphone
     */
    private function maskPhone(string $phone): string
    {
        $length = strlen($phone);
        if ($length <= 6) {
            return $phone;
        }
        return substr($phone, 0, 4) . str_repeat('*', $length - 6) . substr($phone, -2);
    }

    /**
     * Calculer la distance entre deux points (formule Haversine)
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
        
        return $earthRadius * $c;
    }
}
