<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIsClient
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || $user->role !== UserRole::CLIENT) {
            return response()->json([
                'success' => false,
                'message' => 'Accès réservé aux clients.',
                'code' => 'FORBIDDEN_NOT_CLIENT',
            ], 403);
        }

        if ($user->status !== UserStatus::ACTIVE) {
            return response()->json([
                'success' => false,
                'message' => 'Votre compte client n\'est pas actif.',
                'code' => 'CLIENT_NOT_ACTIVE',
            ], 403);
        }

        return $next($request);
    }
}
