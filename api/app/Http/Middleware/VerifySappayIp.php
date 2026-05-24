<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware de whitelist IP pour les webhooks Sappay.
 *
 * Comportement :
 *  - Si SAPPAY_WEBHOOK_ALLOWED_IPS est vide (non configuré) ET qu'on est en
 *    production → 403. Sappay n'a pas encore publié sa liste officielle d'IPs ;
 *    dès qu'elle est disponible, renseigner la variable d'env.
 *  - Si SAPPAY_WEBHOOK_ALLOWED_IPS est vide hors production → laisse passer
 *    (développement local, tests).
 *  - Si la liste est configurée, seules les IPs présentes sont autorisées.
 *
 * Format SAPPAY_WEBHOOK_ALLOWED_IPS : liste séparée par des virgules.
 *   Exemples :
 *     SAPPAY_WEBHOOK_ALLOWED_IPS=41.203.0.0/16,41.204.128.0/19
 *     SAPPAY_WEBHOOK_ALLOWED_IPS=41.203.10.5,41.203.10.6
 *
 * Supporte les IPs exactes et les plages CIDR (/notation).
 */
class VerifySappayIp
{
    public function handle(Request $request, Closure $next): Response
    {
        $allowedRaw = config('sappay.webhook_allowed_ips', '');

        if (empty($allowedRaw)) {
            if (config('app.env') === 'production') {
                Log::channel('security')->warning('VerifySappayIp: liste IPs non configurée — requête bloquée en production', [
                    'ip' => $request->ip(),
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé.',
                ], 403);
            }
            // Hors production : laisser passer sans whitelist
            return $next($request);
        }

        $allowed = array_filter(array_map('trim', explode(',', $allowedRaw)));
        $clientIp = $request->ip();

        foreach ($allowed as $range) {
            if ($this->ipInRange($clientIp, $range)) {
                return $next($request);
            }
        }

        Log::channel('security')->warning('VerifySappayIp: IP non autorisée', [
            'ip'      => $clientIp,
            'allowed' => $allowed,
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Accès non autorisé.',
        ], 403);
    }

    /**
     * Vérifie si $ip appartient à $range (IP exacte ou CIDR).
     */
    private function ipInRange(string $ip, string $range): bool
    {
        if (!str_contains($range, '/')) {
            return $ip === $range;
        }

        [$subnet, $bits] = explode('/', $range, 2);
        $bits = (int) $bits;

        $ipLong     = ip2long($ip);
        $subnetLong = ip2long($subnet);

        if ($ipLong === false || $subnetLong === false) {
            return false;
        }

        $mask = $bits === 0 ? 0 : (~0 << (32 - $bits));

        return ($ipLong & $mask) === ($subnetLong & $mask);
    }
}
