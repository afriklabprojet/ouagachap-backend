<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware d'authentification Sanctum uniquement.
 *
 * Remplace AuthenticateHybridApi depuis la migration Firebase-only.
 * Toute session passe désormais par Firebase → token Sanctum Bearer.
 */
class AuthenticateSanctumApi
{
    private const AUTH_SENSITIVE_HEADERS = [
        'Cache-Control' => 'no-store, no-cache, private, must-revalidate, max-age=0',
        'Pragma' => 'no-cache',
        'Expires' => 'Thu, 01 Jan 1970 00:00:00 GMT',
        'Vary' => 'Authorization',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $this->resolveAuthenticatedUser($request);

        if (! $user) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Token d\'accès manquant ou invalide.',
                'code'    => 'UNAUTHENTICATED',
            ], 401, self::AUTH_SENSITIVE_HEADERS);
        }

        $request->setUserResolver(static fn () => $user);

        return $next($request);
    }

    private function resolveAuthenticatedUser(Request $request): mixed
    {
        $user = array_key_exists('sanctum', config('auth.guards', []))
            ? Auth::guard('sanctum')->user()
            : $request->user();

        if ($user === null) {
            $bearerToken = $request->bearerToken();
            $accessToken = is_string($bearerToken) && $bearerToken !== ''
                ? PersonalAccessToken::findToken($bearerToken)
                : null;

            if ($accessToken instanceof PersonalAccessToken) {
                $tokenable = $accessToken->tokenable;
                if (method_exists($tokenable, 'withAccessToken')) {
                    $tokenable->withAccessToken($accessToken);
                }

                $user = $tokenable;
            }
        }

        return $user;
    }
}
