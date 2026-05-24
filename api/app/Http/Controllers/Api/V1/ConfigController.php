<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\CacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Configuration
 *
 * Endpoints pour obtenir la configuration de l'application.
 */
class ConfigController extends BaseController
{
    public function __construct(
        private readonly CacheService $cacheService,
    ) {}

    /**
     * Configuration WebSocket
     *
     * Retourne les informations de connexion pour le client WebSocket.
     * S'adapte automatiquement au broadcaster configuré (Pusher ou Reverb).
     *
     * @unauthenticated
     * @response 200 {"success": true, "data": {"broadcaster": "pusher", "key": "xxx", "host": "ws-eu.pusher.com", "port": 443, "scheme": "https", "cluster": "eu"}}
     */
    public function websocket(): JsonResponse
    {
        $broadcaster = config('broadcasting.default', 'null');

        if ($broadcaster === 'pusher') {
            $cluster = config('broadcasting.connections.pusher.options.cluster', 'eu');
            $data = [
                'broadcaster' => 'pusher',
                'key' => config('broadcasting.connections.pusher.key'),
                'host' => "ws-{$cluster}.pusher.com",
                'port' => 443,
                'scheme' => 'https',
                'cluster' => $cluster,
                'auth_endpoint' => url('/api/broadcasting/auth'),
            ];
        } else {
            $data = [
                'broadcaster' => 'reverb',
                'key' => config('broadcasting.connections.reverb.key'),
                'host' => config('broadcasting.connections.reverb.options.host'),
                'port' => config('broadcasting.connections.reverb.options.port'),
                'scheme' => config('broadcasting.connections.reverb.options.scheme'),
                'auth_endpoint' => url('/api/broadcasting/auth'),
            ];
        }

        return $this->success($data, 'Configuration WebSocket.');
    }

    /**
     * Zones de livraison
     *
     * Retourne la liste des zones de livraison actives.
     *
     * @unauthenticated
     * @response 200 {"success": true, "data": [{"id": 1, "name": "Centre-ville", "base_price": 500, "price_per_km": 200}]}
     */
    public function zones(): JsonResponse
    {
        $zones = $this->cacheService->getActiveZones()
            ->map->only(['id', 'name', 'code', 'base_price', 'price_per_km']);

        return $this->success($zones, 'Zones de livraison.');
    }

    /**
     * Configuration générale
     *
     * Retourne la configuration générale de l'application.
     *
     * @unauthenticated
     * @response 200 {"success": true, "data": {"app_name": "OUAGA CHAP", "version": "1.0.0", "currency": "XOF", "support_phone": "+22670000000"}}
     */
    public function general(): JsonResponse
    {
        return $this->success([
            'app_name' => config('app.name'),
            'version' => config('app.version', '1.0.0'),
            'currency' => config('app.currency', 'XOF'),
            'currency_symbol' => 'FCFA',
            'min_order_amount' => config('app.min_order_amount', 500),
            'max_order_amount' => config('app.max_order_amount', 100000),
            'min_recharge_amount' => config('app.min_recharge_amount', 100),
            'max_recharge_amount' => config('app.max_recharge_amount', 500000),
            'recharge_amounts' => config('app.recharge_amounts', [500, 1000, 2000, 5000, 10000, 20000]),
            'support_phone' => config('app.support_phone', '+22670000000'),
            'support_email' => config('app.support_email', 'support@ouagachap.com'),
            'terms_url' => url('/terms'),
            'privacy_url' => url('/privacy'),
            'base_fare' => config('app.default_base_price', 500),
            'price_per_km' => config('app.default_price_per_km', 200),
            'min_fare' => config('app.default_base_price', 500),
        ], 'Configuration générale.');
    }
}
