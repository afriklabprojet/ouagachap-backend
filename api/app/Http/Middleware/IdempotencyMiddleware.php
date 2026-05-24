<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Garantit l'idempotence des mutations financières critiques.
 *
 * Le client envoie un header `Idempotency-Key: <uuid-v4>` unique par opération.
 * Si la requête est rejouée avec la même clé dans les 24h, on retourne
 * la réponse mise en cache au lieu de réexécuter l'opération.
 *
 * Routes concernées :
 *   POST /api/v1/sappay/recharge
 *   POST /api/v1/sappay/pay-order
 *   POST /api/v1/sappay/confirm
 *   POST /api/v1/wallet/withdraw
 *   POST /api/v1/wallet/withdraw-direct
 *
 * Usage Flutter :
 *   final key = const Uuid().v4();
 *   headers['Idempotency-Key'] = key;
 *   // Stocker key en local pour retry si réseau coupé
 *
 * RFC de référence : https://datatracker.ietf.org/doc/draft-ietf-httpapi-idempotency-key-header/
 */
class IdempotencyMiddleware
{
    private const CACHE_PREFIX = 'idempotency:';
    private const CACHE_TTL    = 86400; // 24 heures
    private const KEY_MIN_LEN  = 8;
    private const KEY_MAX_LEN  = 128;

    public function handle(Request $request, Closure $next): Response
    {
        $idempotencyKey = $request->header('Idempotency-Key');

        // Clé absente — requête autorisée mais sans protection idempotence
        if (! $idempotencyKey) {
            return $next($request);
        }

        // Validation basique de la clé
        $keyLen = strlen($idempotencyKey);
        if ($keyLen < self::KEY_MIN_LEN || $keyLen > self::KEY_MAX_LEN) {
            return response()->json([
                'success' => false,
                'message' => 'Idempotency-Key invalide (longueur entre ' . self::KEY_MIN_LEN . ' et ' . self::KEY_MAX_LEN . ' caractères).',
                'code'    => 'INVALID_IDEMPOTENCY_KEY',
            ], 422);
        }

        $userId   = $request->user()?->id ?? 'guest';
        $route    = $request->route()?->getName() ?? $request->path();
        $cacheKey = self::CACHE_PREFIX . hash('sha256', "{$userId}:{$route}:{$idempotencyKey}");

        // Vérifier si une réponse en cache existe pour cette clé
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            Log::info('Idempotency: réponse en cache retournée', [
                'key'     => substr($idempotencyKey, 0, 8) . '...',
                'user_id' => $userId,
                'route'   => $route,
            ]);

            return response()->json(
                $cached['body'],
                $cached['status']
            )->header('Idempotency-Replayed', 'true');
        }

        // Exécuter la requête
        $response = $next($request);

        // Mettre en cache uniquement les réponses 2xx (succès ou conflit métier connu)
        if ($response instanceof JsonResponse
            && $response->status() >= 200
            && $response->status() < 300
        ) {
            Cache::put($cacheKey, [
                'body'   => $response->getData(true),
                'status' => $response->status(),
            ], self::CACHE_TTL);
        }

        return $response;
    }
}
