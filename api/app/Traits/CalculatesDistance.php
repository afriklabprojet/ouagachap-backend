<?php

namespace App\Traits;

/**
 * Trait pour le calcul de distance entre deux points GPS (formule Haversine).
 *
 * Remplace les 6+ copies de la même formule dans le codebase.
 */
trait CalculatesDistance
{
    /**
     * Calculer la distance entre deux points GPS en kilomètres.
     *
     * @param float $lat1 Latitude du point 1
     * @param float $lng1 Longitude du point 1
     * @param float $lat2 Latitude du point 2
     * @param float $lng2 Longitude du point 2
     * @return float Distance en kilomètres
     */
    protected function calculateDistanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371; // km

        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lngDelta / 2) * sin($lngDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c, 2);
    }

    /**
     * Calculer la distance entre deux points GPS en mètres.
     */
    protected function calculateDistanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        return $this->calculateDistanceKm($lat1, $lng1, $lat2, $lng2) * 1000;
    }

    /**
     * Retourne l'expression SQL Haversine pour une requête Eloquent/QueryBuilder.
     *
     * Usage :
     *   [$expr, $bindings] = $this->haversineExpression($lat, $lng, 'pickup_latitude', 'pickup_longitude');
     *   $query->selectRaw("*, {$expr} AS distance", $bindings)
     *         ->whereRaw("{$expr} <= ?", [...$bindings, $radiusKm]);
     *
     * @return array{0: string, 1: float[]}  [sql_expression, bindings × 3]
     */
    protected function haversineExpression(
        float $lat,
        float $lng,
        string $latColumn = 'current_latitude',
        string $lngColumn = 'current_longitude',
    ): array {
        $expr = "(6371 * acos("
            . "cos(radians(?)) * cos(radians({$latColumn})) "
            . "* cos(radians({$lngColumn}) - radians(?)) "
            . "+ sin(radians(?)) * sin(radians({$latColumn}))"
            . "))";

        return [$expr, [$lat, $lng, $lat]];
    }
}
