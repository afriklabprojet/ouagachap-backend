<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service météo pour Ouagadougou.
 *
 * Consomme l'API OpenWeatherMap (plan gratuit).
 * Clé config : services.openweathermap.api_key
 * Cache TTL : 15 minutes (données météo changent lentement).
 */
class WeatherService
{
    /**
     * Coordonnées de référence d'Ouagadougou.
     */
    private const OUAGA_LAT = 12.3647;
    private const OUAGA_LNG = 1.5353;

    /**
     * TTL du cache météo en secondes (15 min).
     */
    private const CACHE_TTL = 900;

    /**
     * Score de condition météo par état (100 = parfait, 0 = catastrophique).
     * Basé sur les codes de condition OWM : https://openweathermap.org/weather-conditions
     */
    private const CONDITION_SCORES = [
        'clear'       => 100,  // Ciel dégagé
        'clouds_few'  => 92,   // Quelques nuages
        'clouds_scat' => 80,   // Nuages épars
        'clouds_brok' => 68,   // Ciel nuageux
        'clouds_over' => 55,   // Ciel couvert
        'drizzle'     => 45,   // Bruine
        'rain_light'  => 35,   // Pluie légère
        'rain'        => 20,   // Pluie modérée
        'rain_heavy'  => 8,    // Pluie forte
        'thunderstorm'=> 0,    // Orage
        'dust'        => 15,   // Tempête de sable (fréquent en saison sèche)
        'haze'        => 60,   // Brume
        'fog'         => 30,   // Brouillard
        'snow'        => 10,   // Neige (très rare à Ouaga)
    ];

    public function __construct(
        private readonly string $apiKey = '',
    ) {}

    /**
     * Retourne le score de condition météo actuel à Ouagadougou (0-100).
     *
     * 100 = conditions parfaites pour la livraison.
     * 0   = conditions très dégradées (orage / tempête de sable).
     *
     * Si l'API est indisponible ou la clé manquante, retourne 80 (neutre légèrement dégradé).
     *
     * @codeCoverageIgnore Dépend d'une API externe
     */
    public function getDeliveryScore(?float $lat = null, ?float $lng = null): float
    {
        $key = $apiKey = config('services.openweathermap.api_key', $this->apiKey);

        if (empty($key)) {
            Log::debug('WeatherService: pas de clé API, score neutre retourné');
            return 80.0;
        }

        $useLat = $lat ?? self::OUAGA_LAT;
        $useLng = $lng ?? self::OUAGA_LNG;
        $cacheKey = 'weather_score_' . round($useLat, 2) . '_' . round($useLng, 2);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($useLat, $useLng, $apiKey) {
            return $this->fetchScoreFromApi($useLat, $useLng, $apiKey);
        });
    }

    /**
     * Retourne les données météo brutes pour affichage (pour l'admin dashboard).
     *
     * @codeCoverageIgnore Dépend d'une API externe
     */
    public function getCurrentConditions(): array
    {
        $apiKey = config('services.openweathermap.api_key', $this->apiKey);

        if (empty($apiKey)) {
            return $this->fallbackConditions();
        }

        $cacheKey = 'weather_conditions_ouaga';

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($apiKey) {
            return $this->fetchConditionsFromApi(self::OUAGA_LAT, self::OUAGA_LNG, $apiKey);
        });
    }

    // ==================== PRIVATE ====================

    private function fetchScoreFromApi(float $lat, float $lng, string $apiKey): float
    {
        try {
            $response = Http::timeout(5)->get('https://api.openweathermap.org/data/2.5/weather', [
                'lat'   => $lat,
                'lon'   => $lng,
                'appid' => $apiKey,
                'units' => 'metric',
                'lang'  => 'fr',
            ]);

            if (! $response->successful()) {
                Log::warning('WeatherService: réponse API non-200', ['status' => $response->status()]);
                return 80.0;
            }

            $data = $response->json();

            return $this->computeScoreFromData($data);

        } catch (\Throwable $e) {
            Log::warning('WeatherService: exception lors de la récupération météo', [
                'message' => $e->getMessage(),
            ]);
            return 80.0;
        }
    }

    private function fetchConditionsFromApi(float $lat, float $lng, string $apiKey): array
    {
        try {
            $response = Http::timeout(5)->get('https://api.openweathermap.org/data/2.5/weather', [
                'lat'   => $lat,
                'lon'   => $lng,
                'appid' => $apiKey,
                'units' => 'metric',
                'lang'  => 'fr',
            ]);

            if (! $response->successful()) {
                return $this->fallbackConditions();
            }

            $data = $response->json();
            $score = $this->computeScoreFromData($data);
            $weatherId = $data['weather'][0]['id'] ?? 800;
            $main = $data['weather'][0]['main'] ?? 'Clear';
            $desc = $data['weather'][0]['description'] ?? 'Ciel dégagé';

            return [
                'score'       => $score,
                'condition'   => $main,
                'description' => ucfirst($desc),
                'temp_c'      => $data['main']['temp'] ?? null,
                'humidity'    => $data['main']['humidity'] ?? null,
                'wind_kmh'    => isset($data['wind']['speed']) ? round($data['wind']['speed'] * 3.6) : null,
                'is_rain'     => $this->isRaining($weatherId),
                'is_harmattan' => $this->isHarmattan($data),
                'icon'        => $data['weather'][0]['icon'] ?? null,
            ];

        } catch (\Throwable $e) {
            Log::warning('WeatherService: exception getCurrentConditions', ['message' => $e->getMessage()]);
            return $this->fallbackConditions();
        }
    }

    private function computeScoreFromData(array $data): float
    {
        $weatherId = $data['weather'][0]['id'] ?? 800;
        $windSpeed = $data['wind']['speed'] ?? 0;  // m/s

        $baseScore = $this->scoreFromWeatherId($weatherId);

        // Pénalité vent fort (>15 m/s = 54 km/h → conditions dangereuses)
        if ($windSpeed > 15) {
            $baseScore = max(0, $baseScore - 30);
        } elseif ($windSpeed > 10) {
            $baseScore = max(0, $baseScore - 10);
        }

        // Pénalité Harmattan (vent de sable d'Afrique de l'Ouest)
        if ($this->isHarmattan($data)) {
            $baseScore = max(0, $baseScore - 15);
        }

        return (float) round($baseScore, 1);
    }

    private function scoreFromWeatherId(int $id): float
    {
        return match (true) {
            $id === 800                     => self::CONDITION_SCORES['clear'],
            $id === 801                     => self::CONDITION_SCORES['clouds_few'],
            $id === 802                     => self::CONDITION_SCORES['clouds_scat'],
            $id === 803                     => self::CONDITION_SCORES['clouds_brok'],
            $id === 804                     => self::CONDITION_SCORES['clouds_over'],
            $id >= 300 && $id < 400         => self::CONDITION_SCORES['drizzle'],
            $id >= 500 && $id < 502         => self::CONDITION_SCORES['rain_light'],
            $id === 502 || $id === 503       => self::CONDITION_SCORES['rain'],
            $id >= 503 && $id < 600         => self::CONDITION_SCORES['rain_heavy'],
            $id >= 200 && $id < 300         => self::CONDITION_SCORES['thunderstorm'],
            $id === 731 || $id === 751 || $id === 761 => self::CONDITION_SCORES['dust'],
            $id === 721                     => self::CONDITION_SCORES['haze'],
            $id === 741                     => self::CONDITION_SCORES['fog'],
            $id >= 600 && $id < 700         => self::CONDITION_SCORES['snow'],
            default                         => self::CONDITION_SCORES['clouds_scat'],
        };
    }

    private function isRaining(int $weatherId): bool
    {
        return ($weatherId >= 200 && $weatherId < 600);
    }

    private function isHarmattan(array $data): bool
    {
        // Harmattan : vent de NE/E + faible humidité + poussière (codes 731, 751, 761)
        $weatherId = $data['weather'][0]['id'] ?? 800;
        $humidity = $data['main']['humidity'] ?? 100;
        $windDeg = $data['wind']['deg'] ?? 0;

        $isDustCode = in_array($weatherId, [731, 751, 761]);
        $isNEWind = ($windDeg >= 0 && $windDeg <= 90) || ($windDeg >= 315 && $windDeg <= 360);
        $isDry = $humidity < 30;

        return $isDustCode || ($isNEWind && $isDry);
    }

    private function fallbackConditions(): array
    {
        return [
            'score'        => 80.0,
            'condition'    => 'Unknown',
            'description'  => 'Données météo indisponibles',
            'temp_c'       => null,
            'humidity'     => null,
            'wind_kmh'     => null,
            'is_rain'      => false,
            'is_harmattan' => false,
            'icon'         => null,
        ];
    }
}
