<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware d'authentification Sanctum uniquement.
 *
 * Remplace AuthenticateHybridApi depuis la migration Firebase-only.
 * Toute session passe désormais par Firebase → token Sanctum Bearer.
 */
class AuthenticateSanctumApi
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('sanctum')->user();

        if (! $user) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Token d\'accès manquant ou invalide.',
                'code'    => 'UNAUTHENTICATED',
            ], 401);
        }

        $request->setUserResolver(static fn () => $user);

        return $next($request);
    }
}
