<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Configuration
 *
 * Endpoints pour obtenir la configuration de l'application.
 */
class ConfigController extends BaseController
{
    /**
     * Configuration WebSocket
     *
     * Retourne les informations de connexion pour le client WebSocket.
     * Utilisez ces informations pour configurer Laravel Echo dans Flutter.
     *
     * @unauthenticated
     * @response 200 {"success": true, "data": {"host": "localhost", "port": 8080, "key": "xxx", "scheme": "http", "cluster": "mt1"}}
     */
    public function websocket(): JsonResponse
    {
        return $this->success([
            'broadcaster' => 'reverb',
            'key' => config('broadcasting.connections.reverb.key'),
            'host' => config('broadcasting.connections.reverb.options.host'),
            'port' => config('broadcasting.connections.reverb.options.port'),
            'scheme' => config('broadcasting.connections.reverb.options.scheme'),
            'auth_endpoint' => url('/api/broadcasting/auth'),
        ], 'Configuration WebSocket.');
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
        $zones = \App\Models\Zone::where('is_active', true)
            ->select(['id', 'name', 'code', 'base_price', 'price_per_km'])
            ->get();

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
            'version' => '1.0.0',
            'currency' => 'XOF',
            'currency_symbol' => 'FCFA',
            'min_order_amount' => 500,
            'max_order_amount' => 100000,
            'support_phone' => '+22670000000',
            'support_email' => 'support@ouagachap.com',
            'terms_url' => url('/terms'),
            'privacy_url' => url('/privacy'),
        ], 'Configuration générale.');
    }
}
