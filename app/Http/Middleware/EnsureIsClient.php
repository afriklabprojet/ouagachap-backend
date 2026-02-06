<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
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

        return $next($request);
    }
}
