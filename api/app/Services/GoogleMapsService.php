<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service pour l'intégration Google Maps Directions API
 * Used for route polylines and distance/duration estimates.
 */
class GoogleMapsService
{
    protected string $apiKey;
    protected string $baseUrl = 'https://maps.googleapis.com/maps/api/directions/json';

    public function __construct()
    {
        $this->apiKey = config('services.google_maps.api_key', '');
    }

    /**
     * Get directions between two points.
     * Returns polyline, distance, and duration.
     *
     * @param float $originLat
     * @param float $originLng
     * @param float $destLat
     * @param float $destLng
     * @return array{polyline: string|null, distance_km: float|null, duration_minutes: int|null}
     */
    public function getDirections(
        float $originLat,
        float $originLng,
        float $destLat,
        float $destLng
    ): array {
        if (empty($this->apiKey)) {
            return ['polyline' => null, 'distance_km' => null, 'duration_minutes' => null];
        }

        // Cache key based on rounded coordinates (to ~100m precision)
        $cacheKey = sprintf(
            'directions:%s,%s:%s,%s',
            round($originLat, 3),
            round($originLng, 3),
            round($destLat, 3),
            round($destLng, 3)
        );

        return Cache::remember($cacheKey, 120, function () use ($originLat, $originLng, $destLat, $destLng) {
            try {
                $response = Http::timeout(5)->get($this->baseUrl, [
                    'origin' => "{$originLat},{$originLng}",
                    'destination' => "{$destLat},{$destLng}",
                    'mode' => 'driving',
                    'language' => 'fr',
                    'key' => $this->apiKey,
                ]);

                if (!$response->successful()) {
                    Log::warning('Google Directions API error', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                    return ['polyline' => null, 'distance_km' => null, 'duration_minutes' => null];
                }

                $data = $response->json();

                if (($data['status'] ?? '') !== 'OK' || empty($data['routes'])) {
                    return ['polyline' => null, 'distance_km' => null, 'duration_minutes' => null];
                }

                $route = $data['routes'][0];
                $leg = $route['legs'][0];

                return [
                    'polyline' => $route['overview_polyline']['points'] ?? null,
                    'distance_km' => round(($leg['distance']['value'] ?? 0) / 1000, 2),
                    'duration_minutes' => (int) ceil(($leg['duration']['value'] ?? 0) / 60),
                ];
            } catch (\Exception $e) {
                Log::warning('Google Directions API exception', ['error' => $e->getMessage()]);
                return ['polyline' => null, 'distance_km' => null, 'duration_minutes' => null];
            }
        });
    }
}
