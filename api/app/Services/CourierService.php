<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Events\CourierLocationUpdated;
use App\Events\CourierWentOnline;
use App\Events\OrderTrackingUpdate;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class CourierService
{
    /**
     * Update courier location and broadcast to clients
     */
    public function updateLocation(User $courier, float $latitude, float $longitude): array
    {
        $courier->updateLocation($latitude, $longitude);

        // Trouver la commande active du coursier
        $activeOrder = Order::where('courier_id', $courier->id)
            ->whereIn('status', [OrderStatus::ASSIGNED, OrderStatus::PICKED_UP])
            ->first();

        // Broadcast la mise à jour de position
        event(new CourierLocationUpdated(
            $courier,
            $latitude,
            $longitude,
            $activeOrder?->id
        ));

        // Si commande active, envoyer aussi un tracking update avec ETA
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

    /**
     * Broadcast tracking update with ETA calculation
     */
    protected function broadcastTrackingUpdate(Order $order, float $lat, float $lng): void
    {
        // Calculer la distance restante vers la destination
        $destLat = $order->status === OrderStatus::ASSIGNED 
            ? $order->pickup_latitude 
            : $order->dropoff_latitude;
        $destLng = $order->status === OrderStatus::ASSIGNED 
            ? $order->pickup_longitude 
            : $order->dropoff_longitude;

        $distanceRemaining = $this->calculateDistance($lat, $lng, $destLat, $destLng);
        
        // Estimer le temps (environ 25 km/h en ville)
        $etaMinutes = (int) ceil(($distanceRemaining / 25) * 60);

        event(new OrderTrackingUpdate(
            $order,
            $lat,
            $lng,
            $etaMinutes,
            round($distanceRemaining, 2)
        ));
    }

    /**
     * Calculate distance between two points using Haversine formula
     */
    protected function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371; // km

        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lngDelta / 2) * sin($lngDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Update courier availability
     */
    public function updateAvailability(User $courier, bool $isAvailable): array
    {
        if ($isAvailable && $courier->status !== UserStatus::ACTIVE) {
            return [
                'success' => false,
                'message' => 'Votre compte doit être actif pour être disponible.',
            ];
        }

        $wasAvailable = $courier->is_available;
        $courier->update(['is_available' => $isAvailable]);

        // Notifier les admins si le statut a changé
        if ($wasAvailable !== $isAvailable) {
            event(new CourierWentOnline($courier, $isAvailable ? 'online' : 'offline'));
        }

        return [
            'success' => true,
            'message' => $isAvailable ? 'Vous êtes maintenant en ligne.' : 'Vous êtes maintenant hors ligne.',
            'is_available' => $isAvailable,
        ];
    }

    /**
     * Get available couriers near a location
     */
    public function getAvailableCouriers(
        float $latitude,
        float $longitude,
        float $radiusKm = 5,
        int $limit = 10
    ): Collection {
        // Using Haversine formula to calculate distance
        $haversine = "(6371 * acos(cos(radians(?)) 
                     * cos(radians(current_latitude)) 
                     * cos(radians(current_longitude) - radians(?)) 
                     + sin(radians(?)) 
                     * sin(radians(current_latitude))))";

        return User::selectRaw("*, {$haversine} AS distance", [$latitude, $longitude, $latitude])
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

    /**
     * =======================================================================
     * ALGORITHME DE MATCHING IA - Scoring multi-critères
     * =======================================================================
     * 
     * Pondération des critères:
     * - Distance (40%): Plus proche = meilleur score
     * - Note moyenne (25%): Meilleure note = meilleur score
     * - Temps de réponse (15%): Réponse rapide aux commandes = meilleur score
     * - Charge actuelle (10%): Moins de commandes en cours = meilleur score
     * - Adéquation véhicule (10%): Véhicule adapté au type de colis = meilleur score
     */
    public function getSmartMatchedCouriers(
        float $latitude,
        float $longitude,
        array $orderDetails = [],
        float $radiusKm = 5,
        int $limit = 10
    ): Collection {
        // Using Haversine formula to calculate distance
        $haversine = "(6371 * acos(cos(radians(?)) 
                     * cos(radians(current_latitude)) 
                     * cos(radians(current_longitude) - radians(?)) 
                     + sin(radians(?)) 
                     * sin(radians(current_latitude))))";

        // Récupérer tous les coursiers disponibles dans le rayon
        $couriers = User::selectRaw("*, {$haversine} AS distance", [$latitude, $longitude, $latitude])
            ->couriers()
            ->active()
            ->available()
            ->whereNotNull('current_latitude')
            ->whereNotNull('current_longitude')
            ->where('average_rating', '>=', 3.0) // Seuil qualité minimum
            ->having('distance', '<', $radiusKm)
            ->get();

        if ($couriers->isEmpty()) {
            return $couriers;
        }

        // Calculer le score pour chaque coursier
        $scoredCouriers = $couriers->map(function ($courier) use ($radiusKm, $orderDetails) {
            $score = $this->calculateCourierScore($courier, $radiusKm, $orderDetails);
            $courier->matching_score = $score['total'];
            $courier->score_breakdown = $score['breakdown'];
            return $courier;
        });

        // Trier par score décroissant et limiter
        return $scoredCouriers
            ->sortByDesc('matching_score')
            ->take($limit)
            ->values();
    }

    /**
     * Calcule le score composite d'un coursier pour une commande
     * Score total entre 0 et 100
     */
    protected function calculateCourierScore(
        User $courier,
        float $maxRadius,
        array $orderDetails = []
    ): array {
        // ===== 1. SCORE DISTANCE (40%) =====
        // Plus proche = meilleur score (inverse linéaire)
        $distance = $courier->distance ?? $maxRadius;
        $distanceScore = max(0, 100 - ($distance / $maxRadius * 100));
        
        // ===== 2. SCORE NOTE MOYENNE (25%) =====
        // Note sur 5, convertie en score sur 100
        $rating = $courier->average_rating ?? 3.0;
        $totalRatings = $courier->total_ratings ?? 0;
        
        // Bonus si beaucoup de notes (fiabilité statistique)
        $ratingConfidence = min(1.0, $totalRatings / 20); // Max confidence à 20 notes
        $ratingScore = ($rating / 5) * 100 * (0.7 + 0.3 * $ratingConfidence);
        
        // ===== 3. SCORE TEMPS DE RÉPONSE (15%) =====
        // Basé sur le taux d'acceptation historique et temps moyen de réponse
        $responseScore = $this->calculateResponseScore($courier);
        
        // ===== 4. SCORE CHARGE ACTUELLE (10%) =====
        // Moins de commandes en cours = plus disponible
        $activeOrders = $courier->courierOrders()
            ->whereIn('status', [OrderStatus::ASSIGNED, OrderStatus::PICKED_UP])
            ->count();
        $loadScore = max(0, 100 - ($activeOrders * 50)); // -50 points par commande active
        
        // ===== 5. SCORE VÉHICULE (10%) =====
        // Adéquation du véhicule au type de colis
        $vehicleScore = $this->calculateVehicleScore($courier, $orderDetails);

        // ===== CALCUL SCORE TOTAL PONDÉRÉ =====
        $weights = [
            'distance' => 0.40,
            'rating' => 0.25,
            'response' => 0.15,
            'load' => 0.10,
            'vehicle' => 0.10,
        ];

        $totalScore = 
            ($distanceScore * $weights['distance']) +
            ($ratingScore * $weights['rating']) +
            ($responseScore * $weights['response']) +
            ($loadScore * $weights['load']) +
            ($vehicleScore * $weights['vehicle']);

        return [
            'total' => round($totalScore, 2),
            'breakdown' => [
                'distance' => [
                    'score' => round($distanceScore, 1),
                    'weight' => $weights['distance'],
                    'weighted' => round($distanceScore * $weights['distance'], 1),
                    'detail' => round($distance, 2) . ' km',
                ],
                'rating' => [
                    'score' => round($ratingScore, 1),
                    'weight' => $weights['rating'],
                    'weighted' => round($ratingScore * $weights['rating'], 1),
                    'detail' => "{$rating}/5 ({$totalRatings} avis)",
                ],
                'response' => [
                    'score' => round($responseScore, 1),
                    'weight' => $weights['response'],
                    'weighted' => round($responseScore * $weights['response'], 1),
                ],
                'load' => [
                    'score' => round($loadScore, 1),
                    'weight' => $weights['load'],
                    'weighted' => round($loadScore * $weights['load'], 1),
                    'detail' => "{$activeOrders} commande(s) active(s)",
                ],
                'vehicle' => [
                    'score' => round($vehicleScore, 1),
                    'weight' => $weights['vehicle'],
                    'weighted' => round($vehicleScore * $weights['vehicle'], 1),
                    'detail' => $courier->vehicle_type ?? 'moto',
                ],
            ],
        ];
    }

    /**
     * Calcule le score de réactivité du coursier
     */
    protected function calculateResponseScore(User $courier): float
    {
        // Récupérer les 30 dernières commandes assignées au coursier
        $recentOrders = Order::where('courier_id', $courier->id)
            ->whereNotNull('assigned_at')
            ->orderBy('created_at', 'desc')
            ->limit(30)
            ->get();

        if ($recentOrders->isEmpty()) {
            // Nouveau coursier: score neutre de 70
            return 70.0;
        }

        // Taux d'acceptation (commandes non annulées par le coursier)
        $totalAssigned = $recentOrders->count();
        $completed = $recentOrders->where('status', OrderStatus::DELIVERED)->count();
        $acceptanceRate = $completed / $totalAssigned;

        // Bonus pour temps de réponse rapide (si tracked)
        // Pour l'instant, on utilise seulement le taux d'acceptation
        return $acceptanceRate * 100;
    }

    /**
     * Calcule le score d'adéquation véhicule/colis
     */
    protected function calculateVehicleScore(User $courier, array $orderDetails): float
    {
        $vehicleType = $courier->vehicle_type ?? 'moto';
        $isLarge = $orderDetails['is_large'] ?? false;
        $isFragile = $orderDetails['is_fragile'] ?? false;
        $orderType = $orderDetails['order_type'] ?? 'standard';
        $weight = $orderDetails['weight'] ?? 0;

        $score = 80.0; // Score de base

        // Ajustements selon le type de véhicule et le colis
        switch ($vehicleType) {
            case 'moto':
                // Moto: idéal pour petits colis, pas adapté aux gros
                if ($isLarge || $weight > 20) {
                    $score -= 40;
                }
                if ($orderType === 'food') {
                    $score += 10; // Moto parfait pour livraison food
                }
                break;
                
            case 'tricycle':
                // Tricycle: bon pour colis moyens
                if ($isLarge) {
                    $score += 10;
                }
                if ($weight > 10 && $weight <= 50) {
                    $score += 10;
                }
                break;
                
            case 'voiture':
            case 'car':
                // Voiture: idéal pour gros colis et fragiles
                if ($isLarge) {
                    $score += 20;
                }
                if ($isFragile) {
                    $score += 15; // Plus stable
                }
                if ($weight > 30) {
                    $score += 15;
                }
                // Moins adapté aux petites courses rapides
                if ($orderType === 'food') {
                    $score -= 10;
                }
                break;
                
            case 'camionnette':
            case 'van':
                // Camionnette: parfait pour gros volumes
                if ($isLarge) {
                    $score += 25;
                }
                if ($weight > 50) {
                    $score += 20;
                }
                // Pas économique pour petits colis
                if (!$isLarge && $weight < 10) {
                    $score -= 20;
                }
                break;
        }

        return max(0, min(100, $score));
    }

    /**
     * Get best matched courier for an order (single recommendation)
     */
    public function getBestCourierForOrder(Order $order): ?User
    {
        $orderDetails = [
            'is_large' => $order->is_large ?? false,
            'is_fragile' => $order->is_fragile ?? false,
            'order_type' => $order->order_type ?? 'standard',
            'weight' => $order->weight ?? 0,
        ];

        $couriers = $this->getSmartMatchedCouriers(
            $order->pickup_latitude,
            $order->pickup_longitude,
            $orderDetails,
            radiusKm: 10, // Chercher dans un rayon de 10km
            limit: 1
        );

        return $couriers->first();
    }

    /**
     * Get courier statistics
     */
    public function getCourierStats(User $courier): array
    {
        $todayOrders = $courier->courierOrders()
            ->whereDate('created_at', today())
            ->count();

        $todayEarnings = $courier->courierOrders()
            ->completed()
            ->whereDate('delivered_at', today())
            ->sum('courier_earnings');

        $weekOrders = $courier->courierOrders()
            ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();

        $weekEarnings = $courier->courierOrders()
            ->completed()
            ->whereBetween('delivered_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->sum('courier_earnings');

        $monthOrders = $courier->courierOrders()
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $monthEarnings = $courier->courierOrders()
            ->completed()
            ->whereMonth('delivered_at', now()->month)
            ->whereYear('delivered_at', now()->year)
            ->sum('courier_earnings');

        return [
            'wallet_balance' => $courier->wallet_balance,
            'total_orders' => $courier->total_orders,
            'average_rating' => $courier->average_rating,
            'total_ratings' => $courier->total_ratings,
            'today' => [
                'orders' => $todayOrders,
                'earnings' => $todayEarnings,
            ],
            'this_week' => [
                'orders' => $weekOrders,
                'earnings' => $weekEarnings,
            ],
            'this_month' => [
                'orders' => $monthOrders,
                'earnings' => $monthEarnings,
            ],
        ];
    }

    /**
     * Get courier earnings history
     */
    public function getEarningsHistory(User $courier, int $perPage = 15): LengthAwarePaginator
    {
        return $courier->courierOrders()
            ->completed()
            ->select(['id', 'order_number', 'courier_earnings', 'delivered_at'])
            ->latest('delivered_at')
            ->paginate($perPage);
    }

    /**
     * Get all couriers for admin
     */
    public function getAllCouriers(?string $status = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = User::couriers()->latest();

        if ($status) {
            $query->where('status', $status);
        }

        return $query->paginate($perPage);
    }

    /**
     * Approve courier account
     */
    public function approveCourier(User $courier): array
    {
        if ($courier->role !== UserRole::COURIER) {
            return [
                'success' => false,
                'message' => 'Cet utilisateur n\'est pas un coursier.',
            ];
        }

        $courier->update(['status' => UserStatus::ACTIVE]);

        return [
            'success' => true,
            'message' => 'Compte coursier approuvé.',
            'courier' => $courier,
        ];
    }

    /**
     * Suspend courier account
     */
    public function suspendCourier(User $courier, string $reason): array
    {
        if ($courier->role !== UserRole::COURIER) {
            return [
                'success' => false,
                'message' => 'Cet utilisateur n\'est pas un coursier.',
            ];
        }

        $courier->update([
            'status' => UserStatus::SUSPENDED,
            'is_available' => false,
        ]);

        return [
            'success' => true,
            'message' => 'Compte coursier suspendu.',
            'courier' => $courier,
        ];
    }
}
