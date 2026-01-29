<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Events\OrderAssigned;
use App\Events\OrderCreated;
use App\Events\OrderStatusChanged;
use App\Models\Order;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class OrderService
{
    /**
     * Calculate distance between two coordinates in km
     */
    public function calculateDistance(
        float $lat1,
        float $lon1,
        float $lat2,
        float $lon2
    ): float {
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
     * Get price estimate for a delivery
     */
    public function getEstimate(array $data): array
    {
        $distance = $this->calculateDistance(
            $data['pickup_latitude'],
            $data['pickup_longitude'],
            $data['dropoff_latitude'],
            $data['dropoff_longitude']
        );

        // Get zone or use default pricing
        $zone = isset($data['zone_id']) 
            ? Zone::find($data['zone_id']) 
            : Zone::active()->first();

        if (!$zone) {
            // Default pricing
            $basePrice = 500;
            $pricePerKm = 200;
        } else {
            $basePrice = $zone->base_price;
            $pricePerKm = $zone->price_per_km;
        }

        $distancePrice = $distance * $pricePerKm;
        $totalPrice = $basePrice + $distancePrice;
        $commissionRate = 0.15;
        $commissionAmount = $totalPrice * $commissionRate;
        $courierEarnings = $totalPrice - $commissionAmount;

        return [
            'distance_km' => $distance,
            'base_price' => round($basePrice, 2),
            'distance_price' => round($distancePrice, 2),
            'total_price' => round($totalPrice, 2),
            'commission_amount' => round($commissionAmount, 2),
            'courier_earnings' => round($courierEarnings, 2),
            'currency' => 'XOF',
        ];
    }

    /**
     * Create a new order
     */
    public function createOrder(User $client, array $data): Order
    {
        $estimate = $this->getEstimate($data);

        return DB::transaction(function () use ($client, $data, $estimate) {
            // Normaliser le téléphone du destinataire
            $dropoffPhone = $this->normalizePhone($data['dropoff_contact_phone']);
            
            // Rechercher si le destinataire a un compte
            $recipientUser = User::where('phone', $dropoffPhone)->first();
            
            // Générer un code de confirmation pour le destinataire
            $confirmationCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            
            $order = Order::create([
                'client_id' => $client->id,
                'recipient_user_id' => $recipientUser?->id, // Lier le destinataire s'il existe
                'zone_id' => $data['zone_id'] ?? null,
                'status' => OrderStatus::PENDING,
                
                // Pickup
                'pickup_address' => $data['pickup_address'],
                'pickup_latitude' => $data['pickup_latitude'],
                'pickup_longitude' => $data['pickup_longitude'],
                'pickup_contact_name' => $data['pickup_contact_name'],
                'pickup_contact_phone' => $data['pickup_contact_phone'],
                'pickup_instructions' => $data['pickup_instructions'] ?? null,
                
                // Dropoff
                'dropoff_address' => $data['dropoff_address'],
                'dropoff_latitude' => $data['dropoff_latitude'],
                'dropoff_longitude' => $data['dropoff_longitude'],
                'dropoff_contact_name' => $data['dropoff_contact_name'],
                'dropoff_contact_phone' => $dropoffPhone,
                'dropoff_instructions' => $data['dropoff_instructions'] ?? null,
                
                // Code de confirmation destinataire
                'recipient_confirmation_code' => $confirmationCode,
                
                // Package
                'package_description' => $data['package_description'],
                'package_size' => $data['package_size'] ?? 'small',
                
                // Pricing
                'distance_km' => $estimate['distance_km'],
                'base_price' => $estimate['base_price'],
                'distance_price' => $estimate['distance_price'],
                'total_price' => $estimate['total_price'],
                'commission_amount' => $estimate['commission_amount'],
                'courier_earnings' => $estimate['courier_earnings'],
            ]);

            // Log initial status
            $order->statusHistories()->create([
                'status' => OrderStatus::PENDING,
                'previous_status' => null,
                'changed_by' => $client->id,
            ]);

            // Dispatch event for push notifications
            event(new OrderCreated($order));
            
            // Si le destinataire a un compte, lui envoyer une notification
            if ($recipientUser) {
                $this->notifyRecipient($order, $recipientUser);
            }

            return $order;
        });
    }
    
    /**
     * Normaliser un numéro de téléphone au format E.164
     */
    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        if (!str_starts_with($phone, '+')) {
            $phone = '+226' . ltrim($phone, '0');
        }
        return $phone;
    }
    
    /**
     * Notifier le destinataire qu'un colis lui est envoyé
     */
    private function notifyRecipient(Order $order, User $recipient): void
    {
        try {
            $pushService = app(PushNotificationService::class);
            $pushService->sendToUser(
                $recipient,
                '📦 Un colis vous est envoyé !',
                "{$order->pickup_contact_name} vous envoie un colis. Code: {$order->recipient_confirmation_code}",
                [
                    'type' => 'incoming_order',
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                ]
            );
        } catch (\Exception $e) {
            // Log mais ne pas bloquer la création de commande
            \Log::warning('Failed to notify recipient', [
                'order_id' => $order->id,
                'recipient_id' => $recipient->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get client orders
     */
    public function getClientOrders(User $client, ?string $status = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = Order::with(['courier:id,name,phone,average_rating', 'zone:id,name'])
            ->forClient($client->id)
            ->latest();

        if ($status) {
            $query->where('status', $status);
        }

        return $query->paginate($perPage);
    }

    /**
     * Get courier orders
     */
    public function getCourierOrders(User $courier, ?string $status = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = Order::with(['client:id,name,phone,average_rating', 'zone:id,name'])
            ->forCourier($courier->id)
            ->latest();

        if ($status) {
            $query->where('status', $status);
        }

        return $query->paginate($perPage);
    }

    /**
     * Get available orders for couriers
     */
    public function getAvailableOrders(int $perPage = 15): LengthAwarePaginator
    {
        return Order::with(['client:id,name,phone', 'zone:id,name'])
            ->availableForCouriers()
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get available orders near courier's location
     */
    public function getAvailableOrdersForCourier(
        ?float $latitude = null,
        ?float $longitude = null,
        float $radius = 10
    ): array {
        $orders = Order::with(['client:id,name,phone', 'zone:id,name'])
            ->where('status', OrderStatus::PENDING)
            ->whereNull('courier_id')
            ->latest()
            ->limit(50)
            ->get();
        
        // Si on a des coordonnées, filtrer et trier par distance
        if ($latitude && $longitude) {
            $orders = $orders->map(function ($order) use ($latitude, $longitude) {
                $order->distance = $this->calculateDistance(
                    $latitude,
                    $longitude,
                    (float) $order->pickup_latitude,
                    (float) $order->pickup_longitude
                );
                return $order;
            })
            ->filter(fn($order) => $order->distance <= $radius)
            ->sortBy('distance')
            ->take(20)
            ->values();
        }
        
        return $orders->toArray();
    }

    /**
     * Assign order to courier by IDs
     */
    public function assignCourier($orderId, int $courierId): array
    {
        $order = Order::find($orderId);
        if (!$order) {
            return [
                'success' => false,
                'message' => 'Commande non trouvée.',
            ];
        }
        
        $courier = User::find($courierId);
        if (!$courier) {
            return [
                'success' => false,
                'message' => 'Coursier non trouvé.',
            ];
        }
        
        return $this->assignOrder($order, $courier);
    }

    /**
     * Assign order to courier
     */
    public function assignOrder(Order $order, User $courier): array
    {
        if (!$order->isPending()) {
            return [
                'success' => false,
                'message' => 'Cette commande ne peut plus être acceptée.',
            ];
        }

        // Vérifier si le coursier a déjà une livraison active
        if ($courier->hasActiveDelivery()) {
            return [
                'success' => false,
                'message' => 'Vous avez déjà une livraison en cours. Terminez-la avant d\'en accepter une autre.',
            ];
        }

        if (!$courier->canAcceptOrders()) {
            return [
                'success' => false,
                'message' => 'Vous ne pouvez pas accepter de commandes actuellement.',
            ];
        }

        $result = $order->assign($courier, $courier->id);

        if (!$result) {
            return [
                'success' => false,
                'message' => 'Impossible d\'assigner cette commande.',
            ];
        }

        // Dispatch event for push notification
        event(new OrderAssigned($order->fresh()));

        return [
            'success' => true,
            'message' => 'Commande acceptée avec succès.',
            'order' => $order->fresh(['client', 'zone']),
        ];
    }

    /**
     * Update order status
     */
    public function updateStatus(
        Order $order,
        OrderStatus $newStatus,
        User $user,
        ?string $note = null,
        ?float $latitude = null,
        ?float $longitude = null
    ): array {
        if (!$order->canTransitionTo($newStatus)) {
            return [
                'success' => false,
                'message' => 'Cette transition de statut n\'est pas autorisée.',
            ];
        }

        $previousStatus = $order->status->value;

        $result = match ($newStatus) {
            OrderStatus::PICKED_UP => $order->markAsPickedUp($user->id, $latitude, $longitude),
            OrderStatus::DELIVERED => $order->markAsDelivered($user->id, $latitude, $longitude),
            OrderStatus::CANCELLED => $order->cancel($note ?? 'Annulée', $user->id),
            default => $order->transitionTo($newStatus, $user->id, $note, $latitude, $longitude),
        };

        if (!$result) {
            return [
                'success' => false,
                'message' => 'Impossible de mettre à jour le statut.',
            ];
        }

        // Dispatch event for push notification
        event(new OrderStatusChanged($order->fresh(), $previousStatus, $newStatus->value));

        return [
            'success' => true,
            'message' => 'Statut mis à jour avec succès.',
            'order' => $order->fresh(['client', 'courier', 'zone', 'statusHistories']),
        ];
    }

    /**
     * Get order details with all relations
     */
    public function getOrderDetails(string $orderId): ?Order
    {
        return Order::with([
            'client:id,name,phone,average_rating',
            'courier:id,name,phone,average_rating,vehicle_type,vehicle_plate',
            'zone:id,name',
            'statusHistories' => fn($q) => $q->latest()->limit(10),
            'payment',
        ])->find($orderId);
    }
}
