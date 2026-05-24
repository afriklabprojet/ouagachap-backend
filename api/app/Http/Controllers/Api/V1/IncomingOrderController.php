<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use App\Services\PushNotificationService;
use App\Traits\CalculatesDistance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Contrôleur pour les colis entrants (colis que l'utilisateur va RECEVOIR)
 */
class IncomingOrderController extends BaseController
{
    use CalculatesDistance;

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

        $formatted = collect($orders->items())->map(fn($order) => $this->formatOrder($order));

        return $this->success([
                'orders' => $formatted,
                'pagination' => [
                    'current_page' => $orders->currentPage(),
                    'last_page' => $orders->lastPage(),
                    'per_page' => $orders->perPage(),
                    'total' => $orders->total(),
                ],
                'stats' => $this->getIncomingStats($user),
            ], 'Colis entrants récupérés');
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
            return $this->notFound('Colis non trouvé');
        }

        return $this->success([
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
            ], 'Détails du colis');
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
            ->whereIn('status', OrderStatus::activeStatuses())
            ->with(['courier:id,name,phone,current_latitude,current_longitude,vehicle_type'])
            ->first();

        if (!$order) {
            return $this->notFound('Colis non trouvable ou déjà livré');
        }

        if (!$order->courier) {
            return $this->error('Aucun coursier assigné');
        }

        // Calculer l'ETA approximatif (en minutes)
        $eta = null;
        if ($order->courier->current_latitude && $order->courier->current_longitude) {
            $distance = $this->calculateDistanceKm(
                $order->courier->current_latitude,
                $order->courier->current_longitude,
                $order->dropoff_latitude,
                $order->dropoff_longitude
            );
            // Vitesse moyenne 25 km/h en ville
            $eta = ceil(($distance / 25) * 60);
        }

        return $this->success([
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'tracking_url' => $this->trackingUrl($order),
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
            return $this->notFound('Colis non trouvé ou pas en cours de livraison');
        }

        // Vérifier le code de confirmation
        if ($order->recipient_confirmation_code !== $request->confirmation_code) {
            return $this->error('Code de confirmation incorrect');
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

        return $this->success(null, 'Réception confirmée ! Le coursier a été notifié.');
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
            return $this->notFound('Colis non trouvé. Vérifiez le numéro de commande et votre téléphone.');
        }

        return $this->success([
                'order_number' => $order->order_number,
                'tracking_url' => $this->trackingUrl($order),
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
            ], 'Colis trouvé');
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
     * Lien public de redirection vers le suivi de livraison.
     */
    private function trackingUrl(Order $order): string
    {
        return rtrim((string) config('app.url'), '/')
            . '/tracking/'
            . rawurlencode($order->order_number)
            . '?orderId='
            . rawurlencode((string) $order->id);
    }

    /**
     * Formater un Order pour le retour API (types compatibles Flutter)
     */
    private function formatOrder(Order $order): array
    {
        $courier = null;

        if ($order->courier) {
            $courierLatitude = $order->courier->current_latitude !== null
                ? (float) $order->courier->current_latitude
                : null;
            $courierLongitude = $order->courier->current_longitude !== null
                ? (float) $order->courier->current_longitude
                : null;

            $courier = [
                'id' => (string) $order->courier->id,
                'name' => $order->courier->name,
                'phone' => $order->courier->phone,
                'vehicle_type' => $order->courier->vehicle_type ?? null,
                'current_latitude' => $courierLatitude,
                'current_longitude' => $courierLongitude,
            ];
        }

        return [
            'id' => (string) $order->id,
            'order_number' => $order->order_number,
            'tracking_url' => $this->trackingUrl($order),
            'status' => $order->status instanceof OrderStatus ? $order->status->value : (string) $order->status,
            'status_label' => $order->status instanceof OrderStatus ? $order->status->label() : (string) $order->status,
            'pickup_contact_name' => $order->pickup_contact_name,
            'pickup_contact_phone' => $this->maskPhone($order->pickup_contact_phone ?? ''),
            'pickup_address' => $order->pickup_address,
            'pickup_latitude' => (float) $order->pickup_latitude,
            'pickup_longitude' => (float) $order->pickup_longitude,
            'dropoff_address' => $order->dropoff_address,
            'dropoff_latitude' => (float) $order->dropoff_latitude,
            'dropoff_longitude' => (float) $order->dropoff_longitude,
            'package_description' => $order->package_description,
            'package_size' => $order->package_size ?? 'small',
            'total_price' => (float) $order->total_price,
            'recipient_confirmation_code' => $order->recipient_confirmation_code,
            'recipient_confirmed' => (bool) $order->recipient_confirmed,
            'courier' => $courier,
            'created_at' => $order->created_at?->toIso8601String(),
            'delivered_at' => $order->delivered_at?->toIso8601String(),
        ];
    }
}
