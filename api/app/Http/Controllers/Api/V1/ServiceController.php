<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Services
 *
 * APIs pour la liste des services disponibles
 */
class ServiceController extends Controller
{
    /**
     * Liste des services
     *
     * Récupère la liste des services disponibles pour le client
     *
     * @authenticated
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "services": [
     *       {
     *         "id": "delivery",
     *         "name": "Livraison Express",
     *         "description": "Livraison rapide en moins de 2h",
     *         "icon": "local_shipping",
     *         "color": "#FF9800",
     *         "is_active": true,
     *         "badge": "Express"
     *       }
     *     ],
     *     "promotions": [
     *       {
     *         "id": 1,
     *         "title": "-20% sur votre première commande",
     *         "code": "BIENVENUE20",
     *         "expires_at": "2026-02-28"
     *       }
     *     ]
     *   }
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $services = [
            [
                'id' => 'delivery',
                'name' => 'Livraison Express',
                'description' => 'Livraison rapide en moins de 2h dans Ouagadougou',
                'icon' => 'local_shipping',
                'color' => '#FF9800',
                'background_color' => '#FFF3E0',
                'is_active' => true,
                'badge' => 'Express',
                'route' => '/create-order',
            ],
            [
                'id' => 'orders',
                'name' => 'Mes Commandes',
                'description' => 'Consultez l\'historique de vos livraisons',
                'icon' => 'inventory_2',
                'color' => '#1976D2',
                'background_color' => '#E3F2FD',
                'is_active' => true,
                'badge' => null,
                'route' => '/orders',
            ],
            [
                'id' => 'wallet',
                'name' => 'Portefeuille',
                'description' => 'Rechargez votre compte via Mobile Money',
                'icon' => 'account_balance_wallet',
                'color' => '#388E3C',
                'background_color' => '#E8F5E9',
                'is_active' => true,
                'badge' => null,
                'route' => '/recharge',
            ],
            [
                'id' => 'support',
                'name' => 'Support',
                'description' => 'Assistance 24h/24, 7j/7',
                'icon' => 'support_agent',
                'color' => '#7B1FA2',
                'background_color' => '#F3E5F5',
                'is_active' => true,
                'badge' => 'Live',
                'route' => '/support',
            ],
            [
                'id' => 'notifications',
                'name' => 'Notifications',
                'description' => 'Vos alertes et mises à jour',
                'icon' => 'notifications',
                'color' => '#D32F2F',
                'background_color' => '#FFEBEE',
                'is_active' => true,
                'badge' => null,
                'route' => '/notifications',
            ],
            [
                'id' => 'profile',
                'name' => 'Mon Profil',
                'description' => 'Gérez vos informations personnelles',
                'icon' => 'person',
                'color' => '#00897B',
                'background_color' => '#E0F2F1',
                'is_active' => true,
                'badge' => null,
                'route' => '/profile',
            ],
        ];

        $promotions = [
            [
                'id' => 1,
                'title' => '-20% sur votre première commande',
                'description' => 'Utilisez le code BIENVENUE20 pour bénéficier de 20% de réduction',
                'code' => 'BIENVENUE20',
                'discount_percent' => 20,
                'min_amount' => 500,
                'expires_at' => '2026-02-28',
                'image_url' => null,
            ],
            [
                'id' => 2,
                'title' => 'Livraison gratuite le weekend',
                'description' => 'Les samedis et dimanches, la livraison est offerte',
                'code' => 'WEEKEND',
                'discount_percent' => 100,
                'min_amount' => 1000,
                'expires_at' => '2026-03-31',
                'image_url' => null,
            ],
        ];

        // Stats rapides pour l'utilisateur
        $user = $request->user();
        
        // Compter les commandes actives
        $activeOrdersCount = 0;
        try {
            $activeOrdersCount = $user->clientOrders()
                ->whereNotIn('status', ['delivered', 'cancelled'])
                ->count();
        } catch (\Exception $e) {
            // Ignorer si la relation n'existe pas
        }
        
        // Compter les notifications non lues
        $unreadNotificationsCount = 0;
        try {
            $unreadNotificationsCount = \App\Models\InAppNotification::where('user_id', $user->id)
                ->whereNull('read_at')
                ->count();
        } catch (\Exception $e) {
            // Ignorer si la table n'existe pas
        }
        
        $quickStats = [
            'wallet_balance' => (int) ($user->wallet_balance ?? 0),
            'active_orders' => $activeOrdersCount,
            'total_orders' => $user->total_orders ?? 0,
            'unread_notifications' => $unreadNotificationsCount,
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'services' => $services,
                'promotions' => $promotions,
                'quick_stats' => $quickStats,
            ],
        ]);
    }

    /**
     * Détails d'un service
     *
     * @authenticated
     * @urlParam serviceId string required L'ID du service. Example: delivery
     */
    public function show(Request $request, string $serviceId): JsonResponse
    {
        $serviceDetails = match ($serviceId) {
            'delivery' => [
                'id' => 'delivery',
                'name' => 'Livraison Express',
                'description' => 'Service de livraison rapide dans tout Ouagadougou',
                'features' => [
                    'Livraison en moins de 2h',
                    'Suivi en temps réel',
                    'Assurance colis incluse',
                    'Paiement à la livraison ou par Mobile Money',
                ],
                'pricing' => [
                    'base_price' => 500,
                    'price_per_km' => 100,
                    'min_price' => 500,
                    'max_price' => 5000,
                ],
                'zones' => [
                    'Ouaga 2000',
                    'Zone du Bois',
                    'Cissin',
                    'Dassasgho',
                    'Karpala',
                ],
            ],
            'wallet' => [
                'id' => 'wallet',
                'name' => 'Portefeuille',
                'description' => 'Rechargez votre compte facilement',
                'features' => [
                    'Recharge via Orange Money',
                    'Recharge via Moov Money',
                    'Paiement instantané',
                    'Historique des transactions',
                ],
                'payment_methods' => [
                    [
                        'id' => 'orange_money',
                        'name' => 'Orange Money',
                        'icon' => '🟠',
                        'is_active' => true,
                    ],
                    [
                        'id' => 'moov_money',
                        'name' => 'Moov Money',
                        'icon' => '🔵',
                        'is_active' => true,
                    ],
                ],
                'limits' => [
                    'min_recharge' => 100,
                    'max_recharge' => 500000,
                    'daily_limit' => 1000000,
                ],
            ],
            default => null,
        };

        if (!$serviceDetails) {
            return response()->json([
                'success' => false,
                'message' => 'Service non trouvé',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $serviceDetails,
        ]);
    }
}
