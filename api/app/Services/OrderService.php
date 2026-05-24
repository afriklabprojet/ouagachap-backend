<?php

namespace App\Services;

use App\DTOs\CreateOrderDTO;
use App\Enums\OrderStatus;
use App\Events\OrderAssigned;
use App\Events\OrderCreated;
use App\Events\OrderStatusChanged;
use App\Jobs\DispatchNewOrderJob;
use App\Models\Order;
use App\Models\User;
use App\Models\Zone;
use App\Traits\CalculatesDistance;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderService
{
    private const ERROR_ORDER_NOT_ACCEPTABLE = 'ORDER_NOT_ACCEPTABLE';

    private const ERROR_COURIER_ACTIVE_DELIVERY = 'COURIER_ACTIVE_DELIVERY';

    private const ERROR_COURIER_UNAVAILABLE = 'COURIER_UNAVAILABLE';

    use CalculatesDistance;

    public function __construct(
        protected GeofenceService $geofenceService,
        protected GamificationService $gamificationService,
        protected PushNotificationService $pushNotificationService,
        protected SubscriptionService $subscriptionService,
    ) {}

    /**
     * Calculate distance between two coordinates in km
     */
    public function calculateDistance(
        float $lat1,
        float $lon1,
        float $lat2,
        float $lon2
    ): float {
        return $this->calculateDistanceKm($lat1, $lon1, $lat2, $lon2);
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
        $zone = isset($data['zone_id']) ? \App\Models\Zone::find($data['zone_id']) : null;

        if (! $zone) {
            Log::warning('OrderService: aucune zone tarifaire trouvée, tarif par défaut appliqué', [
                'pickup_lat' => $data['pickup_latitude'],
                'pickup_lng' => $data['pickup_longitude'],
                'zone_id' => $data['zone_id'] ?? null,
            ]);

            try {
                $alert = \App\Models\AutoAlert::firstOrCreate(
                    ['trigger_type' => 'zone_missing'],
                    [
                        'name' => 'Commande hors zone tarifaire',
                        'is_active' => true,
                        'cooldown_minutes' => 30,
                        'conditions' => [],
                        'actions' => ['notify_admin' => true],
                    ]
                );

                if ($alert->canTrigger()) {
                    $alert->update([
                        'conditions' => [
                            'pickup_lat' => $data['pickup_latitude'],
                            'pickup_lng' => $data['pickup_longitude'],
                            'zone_id' => $data['zone_id'] ?? null,
                            'triggered_at' => now()->toIso8601String(),
                        ],
                    ]);
                    $alert->markAsTriggered();
                }
            } catch (\Exception $e) {
                Log::error('AutoAlert zone_missing failed', ['error' => $e->getMessage()]);
            }

            $basePrice = 500;
            $pricePerKm = 200;
        } else {
            $basePrice = $zone->base_price;
            $pricePerKm = $zone->price_per_km;
        }

        $distancePrice = $distance * $pricePerKm;
        $baseTotal = $basePrice + $distancePrice;

        // Tarification dynamique (surge) via GeofenceService (Sprint 1.2)
        $surgeMultiplier = $zone ? $this->geofenceService->getDynamicPricing($zone) : 1.0;
        $isSurge = $surgeMultiplier > 1.0;
        $totalPrice = $baseTotal * $surgeMultiplier;

        // Commission depuis la zone (panel admin) ou fallback .env
        $commissionRate = ($zone && $zone->commission_rate !== null)
            ? (float) $zone->commission_rate
            : config('app.commission_rate', 0.15);
        $commissionAmount = $totalPrice * $commissionRate;
        $courierEarnings = $totalPrice - $commissionAmount;

        return [
            'distance_km' => $distance,
            'base_price' => round($basePrice, 2),
            'distance_price' => round($distancePrice, 2),
            'surge_multiplier' => $surgeMultiplier,
            'is_surge' => $isSurge,
            'total_price' => round($totalPrice, 2),
            'commission_rate' => $commissionRate,
            'commission_amount' => round($commissionAmount, 2),
            'courier_earnings' => round($courierEarnings, 2),
            'currency' => 'XOF',
        ];
    }

    /**
     * Create a new order
     */
    public function createOrder(User $client, CreateOrderDTO $dto): Order
    {
        $estimate = $this->getEstimate($dto->toArray());
        $subscriptionDiscount = $this->subscriptionService->getDiscountForUser($client);
        $discountedTotal = (int) max(0, $estimate['total_price'] - $subscriptionDiscount);
        $commissionRate = (float) ($estimate['commission_rate'] ?? config('app.commission_rate', 0.15));
        $commissionAmount = round($discountedTotal * $commissionRate, 2);
        $courierEarnings = round(max(0, $discountedTotal - $commissionAmount), 2);

        return DB::transaction(function () use ($client, $dto, $estimate, $subscriptionDiscount, $discountedTotal, $commissionAmount, $courierEarnings) {
            // Normaliser le téléphone du destinataire
            $dropoffPhone = $this->normalizePhone($dto->dropoffContactPhone);

            // Rechercher si le destinataire a un compte
            $recipientUser = User::where('phone', $dropoffPhone)->first();

            // Générer un code de confirmation pour le destinataire
            $confirmationCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            // forceFill() contourne $guarded — réservé aux services internes uniquement.
            // Les champs financiers et d'état ne peuvent pas être passés via les contrôleurs.
            $order = (new Order)->forceFill([
                'client_id'                  => $client->id,
                'recipient_user_id'          => $recipientUser?->id,
                'zone_id'                    => $dto->zoneId,
                'status'                     => OrderStatus::PENDING,
                'recipient_confirmation_code'=> $confirmationCode,

                // Pickup
                'pickup_address'             => $dto->pickupAddress,
                'pickup_latitude'            => $dto->pickupLatitude,
                'pickup_longitude'           => $dto->pickupLongitude,
                'pickup_contact_name'        => $dto->pickupContactName,
                'pickup_contact_phone'       => $dto->pickupContactPhone,
                'pickup_instructions'        => $dto->pickupInstructions,

                // Dropoff
                'dropoff_address'            => $dto->dropoffAddress,
                'dropoff_latitude'           => $dto->dropoffLatitude,
                'dropoff_longitude'          => $dto->dropoffLongitude,
                'dropoff_contact_name'       => $dto->dropoffContactName,
                'dropoff_contact_phone'      => $dropoffPhone,
                'dropoff_instructions'       => $dto->dropoffInstructions,

                // Package
                'package_description'        => $dto->packageDescription,
                'package_size'               => $dto->packageSize,
                'payment_method'             => $dto->paymentMethod,

                // Pricing — calculé par l'API, jamais fourni par le client
                'distance_km'                => $estimate['distance_km'],
                'base_price'                 => $estimate['base_price'],
                'distance_price'             => $estimate['distance_price'],
                'total_price'                => $discountedTotal,
                'subscription_discount'      => $subscriptionDiscount,
                'commission_amount'          => $commissionAmount,
                'courier_earnings'           => $courierEarnings,
            ]);
            $order->save();

            // Log initial status
            $order->statusHistories()->create([
                'status' => OrderStatus::PENDING,
                'previous_status' => null,
                'changed_by' => $client->id,
            ]);

            // Dispatch event for push notifications
            event(new OrderCreated($order));

            // Tenter l'assignation immédiate — sans délai, hors transaction
            DispatchNewOrderJob::dispatch($order)->afterCommit();

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
        if (! str_starts_with($phone, '+')) {
            $phone = '+226'.ltrim($phone, '0');
        }

        return $phone;
    }

    /**
     * Notifier le destinataire qu'un colis lui est envoyé
     */
    private function notifyRecipient(Order $order, User $recipient): void
    {
        try {
            $this->pushNotificationService->sendToUser(
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
            Log::warning('Failed to notify recipient', [
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
        $query = Order::with(['client:id,name,phone', 'zone:id,name'])
            ->where('status', OrderStatus::PENDING)
            ->whereNull('courier_id');

        // Si on a des coordonnées, filtrer par distance en SQL
        if ($latitude && $longitude) {
            [$haversine, $bindings] = $this->haversineExpression(
                $latitude, $longitude, 'pickup_latitude', 'pickup_longitude',
            );

            $query->selectRaw("*, {$haversine} AS distance", $bindings)
                ->whereRaw("{$haversine} <= ?", [...$bindings, $radius])
                ->orderByRaw("{$haversine}", $bindings);
        } else {
            $query->latest();
        }

        return $query->limit(20)->get()->toArray();
    }

    /**
     * Assign order to courier by IDs
     */
    public function assignCourier(string $orderId, int $courierId): array
    {
        $order = Order::find($orderId);
        if (! $order) {
            return [
                'success' => false,
                'message' => 'Commande non trouvée.',
            ];
        }

        $courier = User::find($courierId);
        if (! $courier) {
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
        return DB::transaction(function () use ($order, $courier) {
            $order = Order::lockForUpdate()->find($order->id);
            $failure = $this->getAssignmentFailure($order, $courier);

            if ($failure !== null) {
                return [
                    'success' => false,
                    'message' => $failure['message'],
                    'error_code' => $failure['error_code'],
                ];
            }

            $result = $order->assign($courier, $courier->id);

            if (! $result) {
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
        });
    }

    /**
     * @return array{message: string, error_code: string}|null
     */
    private function getAssignmentFailure(?Order $order, User $courier): ?array
    {
        $failure = null;

        if (! $order || ! $order->isPending()) {
            $failure = [
                'message' => 'Cette commande ne peut plus être acceptée.',
                'error_code' => self::ERROR_ORDER_NOT_ACCEPTABLE,
            ];
        } elseif ($courier->hasActiveDelivery()) {
            $failure = [
                'message' => 'Vous avez déjà une livraison en cours. Terminez-la avant d\'en accepter une autre.',
                'error_code' => self::ERROR_COURIER_ACTIVE_DELIVERY,
            ];
        } elseif (! $courier->canAcceptOrders()) {
            $failure = [
                'message' => 'Vous ne pouvez pas accepter de commandes actuellement.',
                'error_code' => self::ERROR_COURIER_UNAVAILABLE,
            ];
        }

        return $failure;
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
        if (! $order->canTransitionTo($newStatus)) {
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

        if (! $result) {
            return [
                'success' => false,
                'message' => 'Impossible de mettre à jour le statut.',
            ];
        }

        // Gamification — hook livraison complète
        if ($newStatus === OrderStatus::DELIVERED && $order->courier_id) {
            $this->gamificationService->onOrderDelivered($order->courier, $order->fresh());
        }

        // Parrainage — récompense à la première livraison du client
        if ($newStatus === OrderStatus::DELIVERED) {
            try {
                app(\App\Services\ReferralService::class)->rewardReferralOnFirstDelivery($order->client);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('ReferralService::rewardReferralOnFirstDelivery failed', [
                    'order_id' => $order->id,
                    'client_id' => $order->client_id,
                    'error' => $e->getMessage(),
                ]);
            }
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
            'courier:id,name,phone,avatar,average_rating,vehicle_type,vehicle_plate',
            'zone:id,name',
            'statusHistories' => fn ($q) => $q->latest()->limit(10),
            'payment',
        ])->find($orderId);
    }
}
