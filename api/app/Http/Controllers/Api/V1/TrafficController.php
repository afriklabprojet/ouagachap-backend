<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\TrafficIncident;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Incidents de Trafic
 *
 * APIs pour le signalement et la consultation des incidents de circulation
 */
class TrafficController extends BaseController
{
    /**
     * Liste des incidents actifs
     *
     * Retourne tous les incidents de trafic actifs, triés par sévérité.
     *
     * @authenticated
     * @queryParam lat numeric Latitude du centre de recherche. Example: 12.3641
     * @queryParam lng numeric Longitude du centre de recherche. Example: -1.5333
     * @queryParam radius numeric Rayon en km (défaut: 10). Example: 5
     * @queryParam type string Filtrer par type. Example: congestion
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'radius' => 'sometimes|numeric|min:0.1|max:100',
        ]);

        $severityOrder = "CASE severity WHEN 'severe' THEN 1 WHEN 'high' THEN 2 WHEN 'moderate' THEN 3 WHEN 'low' THEN 4 ELSE 5 END";

        $query = TrafficIncident::active()
            ->with(['reporter:id,name'])
            ->orderByRaw($severityOrder)
            ->orderBy('confirmations', 'desc');

        // Filtre optionnel par type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $incidents = $query->get();

        return $this->success($incidents);
    }

    /**
     * Signaler un incident
     *
     * Permet à un utilisateur de signaler un nouvel incident de trafic.
     *
     * @authenticated
     * @bodyParam type string required Type d'incident. Enum: congestion, accident, road_work, road_closed, police, hazard. Example: congestion
     * @bodyParam severity string required Sévérité. Enum: low, moderate, high, severe. Example: moderate
     * @bodyParam latitude numeric required Latitude. Example: 12.3641
     * @bodyParam longitude numeric required Longitude. Example: -1.5333
     * @bodyParam address string Adresse approximative. Example: Rond-point des Nations
     * @bodyParam description string Description de l'incident. Example: Embouteillage important
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:congestion,accident,road_work,road_closed,police,hazard',
            'severity' => 'required|in:low,moderate,high,severe',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'address' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        $incident = TrafficIncident::create([
            ...$validated,
            'reporter_id' => $request->user()->id,
            'confirmations' => 1,
            'is_active' => true,
            'expires_at' => now()->addHours(2),
        ]);

        return $this->success($incident, 'Incident signalé avec succès', 201);
    }

    /**
     * Confirmer un incident
     *
     * Incrémente le compteur de confirmations d'un incident existant.
     *
     * @authenticated
     * @urlParam incident string required UUID de l'incident. Example: 550e8400-e29b-41d4-a716-446655440000
     */
    public function confirm(Request $request, string $incident): JsonResponse
    {
        $trafficIncident = TrafficIncident::find($incident);

        if (!$trafficIncident) {
            return $this->notFound('Incident non trouvé.');
        }

        if (!$trafficIncident->is_active || $trafficIncident->isExpired()) {
            return $this->error('Cet incident n\'est plus actif.', 400);
        }

        // Empêcher le reporter de confirmer son propre incident
        if ($trafficIncident->reporter_id === $request->user()->id) {
            return $this->error('Vous ne pouvez pas confirmer votre propre signalement.', 422);
        }

        $trafficIncident->confirm();
        $trafficIncident->refresh();

        // Extend expiration by 30 minutes when confirmations reach 3+
        if ($trafficIncident->confirmations >= 3) {
            $trafficIncident->update([
                'expires_at' => $trafficIncident->expires_at->addMinutes(30),
            ]);
        }

        return $this->success(
            ['confirmations' => $trafficIncident->fresh()->confirmations],
            'Incident confirmé'
        );
    }

    /**
     * Résoudre un incident
     *
     * Marque un incident comme résolu (accès admin ou reporter).
     *
     * @authenticated
     * @urlParam incident string required UUID de l'incident. Example: 550e8400-e29b-41d4-a716-446655440000
     */
    public function resolve(Request $request, string $incident): JsonResponse
    {
        $trafficIncident = TrafficIncident::findOrFail($incident);

        if (!$trafficIncident->is_active) {
            return $this->error('Cet incident est déjà résolu.', 400);
        }

        $trafficIncident->resolve($request->user()->id);

        return $this->success(null, 'Incident résolu');
    }

    /**
     * Types d'incidents disponibles
     *
     * Retourne la liste des types et sévérités d'incidents disponibles.
     */
    public function types(): JsonResponse
    {
        return $this->success([
            'types' => [
                'congestion' => 'Embouteillage',
                'accident' => 'Accident',
                'road_work' => 'Travaux',
                'road_closed' => 'Route fermée',
                'police' => 'Contrôle de police',
                'hazard' => 'Danger',
            ],
            'severities' => [
                'low' => 'Faible',
                'moderate' => 'Modéré',
                'high' => 'Élevé',
                'severe' => 'Sévère',
            ],
        ]);
    }

    /**
     * Statistiques des incidents
     *
     * Retourne les statistiques des incidents de trafic.
     *
     * @authenticated
     */
    public function stats(Request $request): JsonResponse
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius' => 'sometimes|numeric|min:0.1|max:100',
        ]);

        $activeQuery = TrafficIncident::active();
        $totalIncidents = (clone $activeQuery)->count();

        $byType = (clone $activeQuery)
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type');

        $bySeverity = (clone $activeQuery)
            ->selectRaw('severity, COUNT(*) as count')
            ->groupBy('severity')
            ->pluck('count', 'severity');

        $severeCount = (clone $activeQuery)->whereIn('severity', ['severe'])->count();
        $highCount = (clone $activeQuery)->whereIn('severity', ['high'])->count();

        // Traffic level based on active incidents
        $trafficLevel = match (true) {
            $severeCount > 0 => 'severe',
            $highCount > 0   => 'high',
            $totalIncidents > 0 => 'moderate',
            default          => 'clear',
        };

        $stats = [
            'total_incidents' => $totalIncidents,
            'by_type' => $byType,
            'by_severity' => $bySeverity,
            'severe_count' => $severeCount,
            'high_count' => $highCount,
            'traffic_level' => $trafficLevel,
            'resolved_today' => TrafficIncident::whereDate('resolved_at', today())
                ->where('is_active', false)
                ->count(),
        ];

        return $this->success($stats);
    }
}
