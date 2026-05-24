<?php

namespace App\Services;

use App\Enums\KycStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Events\CourierLocationUpdated;
use App\Events\CourierWentOnline;
use App\Events\OrderTrackingUpdate;
use App\Models\Order;
use App\Models\OrderLocationHistory;
use App\Models\User;
use App\Traits\CalculatesDistance;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class CourierService
{
    use CalculatesDistance;

    /** Solde minimum (FCFA) requis pour qu'un coursier puisse être en ligne */
    public const MINIMUM_WALLET_BALANCE = 2000;

    public function __construct(
        protected GoogleMapsService $googleMapsService,
    ) {}

    // -------------------------------------------------------------------------
    // Location
    // -------------------------------------------------------------------------

    public function updateLocation(
        User $courier,
        float $latitude,
        float $longitude,
        ?float $heading = null,
        ?float $speed = null,
        ?float $accuracy = null,
    ): array {
        $courier->updateLocation($latitude, $longitude);

        $activeOrder = Order::where('courier_id', $courier->id)
            ->whereIn('status', OrderStatus::activeStatuses())
            ->first();

        if ($activeOrder) {
            OrderLocationHistory::create([
                'order_id' => $activeOrder->id,
                'courier_id' => $courier->id,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'heading' => $heading,
                'speed' => $speed,
                'accuracy' => $accuracy,
                'recorded_at' => now(),
            ]);
        }

        event(new CourierLocationUpdated($courier, $latitude, $longitude, $activeOrder?->id));

        if ($activeOrder) {
            $this->broadcastTrackingUpdate($activeOrder, $latitude, $longitude);
        }

        return [
            'success' => true,
            'message' => 'Position mise à jour.',
            'location' => [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'updated_at' => now()->toIso8601String(),
            ],
        ];
    }

    protected function broadcastTrackingUpdate(Order $order, float $lat, float $lng): void
    {
        $destLat = $order->status === OrderStatus::ASSIGNED
            ? $order->pickup_latitude
            : $order->dropoff_latitude;
        $destLng = $order->status === OrderStatus::ASSIGNED
            ? $order->pickup_longitude
            : $order->dropoff_longitude;

        $distanceRemaining = $this->calculateDistance($lat, $lng, $destLat, $destLng);

        $directions = $this->googleMapsService->getDirections($lat, $lng, $destLat, $destLng);
        $etaMinutes = $directions['duration_minutes'] ?? (int) ceil(($distanceRemaining / 25) * 60);

        event(new OrderTrackingUpdate($order, $lat, $lng, $etaMinutes, round($distanceRemaining, 2)));
    }

    protected function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        return $this->calculateDistanceKm($lat1, $lng1, $lat2, $lng2);
    }

    // -------------------------------------------------------------------------
    // Availability
    // -------------------------------------------------------------------------

    public function updateAvailability(User $courier, bool $isAvailable): array
    {
        if ($isAvailable) {
            $error = $this->checkAvailabilityConditions($courier);
            if ($error !== null) {
                return $error;
            }
        }

        $wasAvailable = $courier->is_available;
        $courier->update(['is_available' => $isAvailable]);

        if ($wasAvailable !== $isAvailable) {
            event(new CourierWentOnline($courier, $isAvailable ? 'online' : 'offline'));
        }

        return [
            'success' => true,
            'message' => $isAvailable ? 'Vous êtes maintenant en ligne.' : 'Vous êtes maintenant hors ligne.',
            'is_available' => $isAvailable,
        ];
    }

    private function checkAvailabilityConditions(User $courier): ?array
    {
        app(WalletService::class)->getOrCreateWallet($courier);
        $courier->syncWalletBalance();
        $courier->refresh();

        $accountError = $this->checkAccountEligibility($courier);
        if ($accountError !== null) {
            return $accountError;
        }

        if ((float) $courier->wallet_balance < self::MINIMUM_WALLET_BALANCE) {
            return [
                'success' => false,
                'message' => 'Solde insuffisant. Vous devez avoir au moins '.number_format(self::MINIMUM_WALLET_BALANCE, 0, '.', ' ').' FCFA dans votre wallet pour recevoir des commandes.',
                'error_code' => 'wallet_insufficient',
                'current_balance' => (float) $courier->wallet_balance,
                'minimum_balance' => self::MINIMUM_WALLET_BALANCE,
            ];
        }

        return null;
    }

    private function checkAccountEligibility(User $courier): ?array
    {
        if ($courier->status !== UserStatus::ACTIVE) {
            return ['success' => false, 'message' => 'Votre compte doit être actif pour être disponible.'];
        }

        if ($courier->role === UserRole::COURIER && $courier->kyc_status !== KycStatus::APPROVED) {
            return [
                'success' => false,
                'message' => 'Votre identité doit être vérifiée avant de pouvoir prendre des commandes.',
                'kyc_status' => $courier->kyc_status,
            ];
        }

        return null;
    }

    // -------------------------------------------------------------------------
    // Queries
    // -------------------------------------------------------------------------

    public function getAvailableCouriers(
        float $latitude,
        float $longitude,
        float $radiusKm = 5,
        int $limit = 10
    ): Collection {
        [$haversine, $bindings] = $this->haversineExpression(
            $latitude, $longitude, 'current_latitude', 'current_longitude',
        );

        return User::selectRaw("*, {$haversine} AS distance", $bindings)
            ->couriers()
            ->active()
            ->available()
            ->whereNotNull('current_latitude')
            ->whereNotNull('current_longitude')
            ->having('distance', '<', $radiusKm)
            ->orderBy('distance')
            ->limit($limit)
            ->get();
    }

    public function getCourierStats(User $courier): array
    {
        app(WalletService::class)->getOrCreateWallet($courier);
        $courier->syncWalletBalance();
        $courier->refresh();

        $todayOrders = $courier->courierOrders()->completed()->whereDate('delivered_at', today())->count();
        $todayEarnings = $courier->courierOrders()->completed()->whereDate('delivered_at', today())->sum('courier_earnings');
        $weekOrders = $courier->courierOrders()->completed()->whereBetween('delivered_at', [now()->startOfWeek(), now()->endOfWeek()])->count();
        $weekEarnings = $courier->courierOrders()->completed()->whereBetween('delivered_at', [now()->startOfWeek(), now()->endOfWeek()])->sum('courier_earnings');
        $monthOrders = $courier->courierOrders()->completed()->whereMonth('delivered_at', now()->month)->whereYear('delivered_at', now()->year)->count();
        $monthEarnings = $courier->courierOrders()->completed()->whereMonth('delivered_at', now()->month)->whereYear('delivered_at', now()->year)->sum('courier_earnings');
        $totalDeliveredOrders = $courier->courierOrders()->completed()->count();

        if ((int) $courier->total_orders !== $totalDeliveredOrders) {
            $courier->forceFill(['total_orders' => $totalDeliveredOrders])->saveQuietly();
            $courier->refresh();
        }

        return [
            'wallet_balance' => $courier->wallet_balance,
            'total_orders' => $totalDeliveredOrders,
            'total_deliveries' => $totalDeliveredOrders,
            'average_rating' => $courier->average_rating,
            'total_ratings' => $courier->total_ratings,
            'today' => ['orders' => $todayOrders,   'deliveries' => $todayOrders, 'earnings' => $todayEarnings],
            'this_week' => ['orders' => $weekOrders,    'deliveries' => $weekOrders, 'earnings' => $weekEarnings],
            'this_month' => ['orders' => $monthOrders,   'deliveries' => $monthOrders, 'earnings' => $monthEarnings],
        ];
    }

    public function getEarningsHistory(User $courier, int $perPage = 15): LengthAwarePaginator
    {
        return $courier->courierOrders()
            ->completed()
            ->select(['id', 'order_number', 'courier_earnings', 'delivered_at'])
            ->latest('delivered_at')
            ->paginate($perPage);
    }

    // -------------------------------------------------------------------------
    // Admin
    // -------------------------------------------------------------------------

    public function getAllCouriers(?string $status = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = User::couriers()->latest();

        if ($status) {
            $query->where('status', $status);
        }

        return $query->paginate($perPage);
    }

    public function approveCourier(User $courier): array
    {
        if ($courier->role !== UserRole::COURIER) {
            return ['success' => false, 'message' => "Cet utilisateur n'est pas un coursier."];
        }

        $courier->update(['status' => UserStatus::ACTIVE]);

        return ['success' => true, 'message' => 'Compte coursier approuvé.', 'courier' => $courier];
    }

    public function suspendCourier(User $courier, string $reason): array
    {
        if ($courier->role !== UserRole::COURIER) {
            return ['success' => false, 'message' => "Cet utilisateur n'est pas un coursier."];
        }

        $courier->update(['status' => UserStatus::SUSPENDED, 'is_available' => false]);

        return ['success' => true, 'message' => "Compte coursier suspendu. Raison : {$reason}", 'courier' => $courier];
    }
}
