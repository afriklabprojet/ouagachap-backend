<?php

namespace App\Http\Middleware;

use App\Enums\UserStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Vérifie que l'utilisateur authentifié est toujours actif.
 * Bloque les requêtes des utilisateurs suspendus même si leur token est encore valide.
 */
class EnsureUserActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->status !== UserStatus::ACTIVE) {
            // Révoquer le token actuel pour forcer la re-connexion
            $currentToken = $user->currentAccessToken();
            if ($currentToken !== null) {
                $currentToken->delete();
            }

            $message = match ($user->status) {
                UserStatus::SUSPENDED => 'Votre compte est suspendu. Contactez le support.',
                UserStatus::PENDING => 'Votre compte est en attente de validation.',
                UserStatus::REJECTED => 'Votre compte a été rejeté.', // @codeCoverageIgnoreStart
                default => 'Votre compte n\'est pas actif.', // @codeCoverageIgnoreEnd
            };

            return response()->json([
                'success' => false,
                'message' => $message,
                'code' => 'ACCOUNT_NOT_ACTIVE',
            ], 403);
        }

        return $next($request);
    }
}
