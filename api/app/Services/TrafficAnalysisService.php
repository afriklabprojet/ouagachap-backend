<?php

namespace App\Services;

use App\Models\TrafficIncident;
use App\Traits\CalculatesDistance;

/**
 * Analyse l'impact des incidents de trafic sur le score d'un coursier.
 *
 * Retourne un score entre 0 et 100 :
 *  - 100 = aucun incident dans la zone
 *  - 0   = incidents très graves à proximité immédiate
 */
class TrafficAnalysisService
{
    use CalculatesDistance;

    /**
     * Pénalités par sévérité (en points, soustraits de 100).
     */
    private const SEVERITY_PENALTIES = [
        'severe'   => 55,
        'high'     => 35,
        'moderate' => 18,
        'low'      => 6,
    ];

    /**
     * Retourne le score d'impact trafic en un point GPS (0-100).
     *
     * Les incidents proches pèsent davantage : pénalité × (1 - distance/radius).
     *
     * @param  float  $lat      Latitude de référence (position du coursier)
     * @param  float  $lng      Longitude de référence
     * @param  float  $radiusKm Rayon d'analyse en kilomètres
     * @return float  Score trafic (100 = circulation fluide, 0 = trafic très perturbé)
     */
    public function getTrafficImpactScore(float $lat, float $lng, float $radiusKm = 2.5): float
    {
        $incidents = TrafficIncident::active()->get();

        if ($incidents->isEmpty()) {
            return 100.0;
        }

        $totalPenalty = 0.0;

        foreach ($incidents as $incident) {
            if ($incident->latitude === null || $incident->longitude === null) {
                continue;
            }

            $distance = $this->calculateDistanceKm(
                $lat, $lng,
                (float) $incident->latitude,
                (float) $incident->longitude
            );

            if ($distance > $radiusKm) {
                continue;
            }

            $severity = $incident->severity ?? 'low';
            $basePenalty = self::SEVERITY_PENALTIES[$severity] ?? self::SEVERITY_PENALTIES['low'];

            // Plus l'incident est proche, plus la pénalité est forte
            $proximityFactor = 1.0 - ($distance / $radiusKm);
            $weightedPenalty = $basePenalty * $proximityFactor;

            // Incidents confirmés par plusieurs utilisateurs = +30% de pénalité
            if (($incident->confirmations ?? 0) >= 3) {
                $weightedPenalty *= 1.30;
            }

            $totalPenalty += $weightedPenalty;
        }

        return (float) max(0.0, round(100.0 - $totalPenalty, 1));
    }

    /**
     * Retourne la liste des incidents actifs avec leur distance par rapport à un point.
     * Utile pour afficher la carte dans le dashboard admin.
     *
     * @return array<array{incident: TrafficIncident, distance_km: float}>
     */
    public function getNearbyIncidents(float $lat, float $lng, float $radiusKm = 5.0): array
    {
        $incidents = TrafficIncident::active()->get();
        $result = [];

        foreach ($incidents as $incident) {
            if ($incident->latitude === null || $incident->longitude === null) {
                continue;
            }

            $distance = $this->calculateDistanceKm(
                $lat, $lng,
                (float) $incident->latitude,
                (float) $incident->longitude
            );

            if ($distance <= $radiusKm) {
                $result[] = [
                    'incident'    => $incident,
                    'distance_km' => round($distance, 2),
                ];
            }
        }

        usort($result, fn ($a, $b) => $a['distance_km'] <=> $b['distance_km']);

        return $result;
    }

    /**
     * Vérifie si une route entre deux points est fortement impactée par le trafic.
     * Utilise un calcul approximatif le long du segment GPS.
     *
     * @return bool true si le trajet est fortement perturbé (score < 40)
     */
    public function isRouteSeverelyImpacted(
        float $fromLat, float $fromLng,
        float $toLat, float $toLng,
        float $radiusKm = 1.5
    ): bool {
        // Vérifier les deux extrémités et le point médian
        $midLat = ($fromLat + $toLat) / 2;
        $midLng = ($fromLng + $toLng) / 2;

        $startScore = $this->getTrafficImpactScore($fromLat, $fromLng, $radiusKm);
        $midScore   = $this->getTrafficImpactScore($midLat, $midLng, $radiusKm);
        $endScore   = $this->getTrafficImpactScore($toLat, $toLng, $radiusKm);

        $avgScore = ($startScore + $midScore + $endScore) / 3;

        return $avgScore < 40;
    }
}
