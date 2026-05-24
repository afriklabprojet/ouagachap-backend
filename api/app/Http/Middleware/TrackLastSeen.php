<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Track the last_seen_at timestamp for authenticated users.
 * Throttled to update at most once every 5 minutes to reduce DB writes.
 */
class TrackLastSeen
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $user = $request->user();

        $lastSeen = $user?->last_seen_at;
        $shouldUpdate = !$lastSeen || ($lastSeen instanceof \DateTimeInterface && $lastSeen->lt(now()->subMinutes(5))) || (is_string($lastSeen) && strtotime($lastSeen) < strtotime('-5 minutes'));

        if ($user && $shouldUpdate) {
            // Silent update, no model events
            DB::table('users')
                ->where('id', $user->id)
                ->update(['last_seen_at' => now()]);
        }

        return $response;
    }
}
