<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIsCourier
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || $user->role !== UserRole::COURIER) {
            return response()->json([
                'success' => false,
                'message' => 'Accès réservé aux coursiers.',
                'code' => 'FORBIDDEN_NOT_COURIER',
            ], 403);
        }

        if ($user->status !== UserStatus::ACTIVE) {
            return response()->json([
                'success' => false,
                'message' => 'Votre compte coursier n\'est pas actif.',
                'code' => 'COURIER_NOT_ACTIVE',
            ], 403);
        }

        return $next($request);
    }
}
