<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || $user->role !== UserRole::ADMIN) {
            return response()->json([
                'success' => false,
                'message' => 'Accès réservé aux administrateurs.',
                'code' => 'FORBIDDEN_NOT_ADMIN',
            ], 403);
        }

        return $next($request);
    }
}
