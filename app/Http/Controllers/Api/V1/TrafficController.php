<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\TrafficIncident;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrafficController extends BaseController
{

    /**
     * Get active traffic incidents near a location
     * GET /api/v1/traffic/incidents
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius' => ['nullable', 'numeric', 'min:1', 'max:50'], // km
        ]);

        $radius = $validated['radius'] ?? 10; // 10km par défaut

        $incidents = TrafficIncident::active()
            ->nearby($validated['latitude'], $validated['longitude'], $radius)
            ->with('reporter:id,name')
            ->get();

        return $this->success($incidents, 'Incidents de trafic récupérés.');
    }

    /**
     * Report a new traffic incident
     * POST /api/v1/traffic/incidents
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'in:congestion,accident,road_work,road_closed,police,hazard'],
            'severity' => ['required', 'string', 'in:low,moderate,high,severe'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'address' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'duration_hours' => ['nullable', 'numeric', 'min:0.5', 'max:48'],
        ]);

        // Calculer l'expiration selon le type
        $expiresAt = $this->calculateExpiration($validated['type'], $validated['duration_hours'] ?? null);

        $incident = TrafficIncident::create([
            'reporter_id' => $request->user()?->id,
            'type' => $validated['type'],
            'severity' => $validated['severity'],
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'address' => $validated['address'] ?? null,
            'description' => $validated['description'] ?? null,
            'confirmations' => 1,
            'is_active' => true,
            'expires_at' => $expiresAt,
        ]);

        return $this->success($incident, 'Incident signalé avec succès.', 201);
    }

    /**
     * Confirm an existing incident
     * POST /api/v1/traffic/incidents/{incident}/confirm
     */
    public function confirm(Request $request, string $incidentId): JsonResponse
    {
        $incident = TrafficIncident::find($incidentId);

        if (!$incident) {
            return $this->notFound('Incident non trouvé.');
        }

        if (!$incident->is_active) {
            return $this->error('Cet incident a déjà été résolu.');
        }

        $incident->confirm();

        // Prolonger l'expiration si beaucoup de confirmations
        if ($incident->confirmations >= 3 && $incident->expires_at) {
            $incident->update([
                'expires_at' => $incident->expires_at->addMinutes(30),
            ]);
        }

        return $this->success($incident->fresh(), 'Incident confirmé.');
    }

    /**
     * Resolve/dismiss an incident
     * POST /api/v1/traffic/incidents/{incident}/resolve
     */
    public function resolve(Request $request, string $incidentId): JsonResponse
    {
        $incident = TrafficIncident::find($incidentId);

        if (!$incident) {
            return $this->notFound('Incident non trouvé.');
        }

        if (!$incident->is_active) {
            return $this->error('Cet incident est déjà résolu.');
        }

        $incident->resolve($request->user()?->id);

        return $this->success($incident->fresh(), 'Incident marqué comme résolu.');
    }

    /**
     * Get incident types
     * GET /api/v1/traffic/types
     */
    public function types(): JsonResponse
    {
        return $this->success([
            'types' => TrafficIncident::getTypes(),
            'severities' => TrafficIncident::getSeverities(),
        ]);
    }

    /**
     * Get traffic statistics for a zone
     * GET /api/v1/traffic/stats
     */
    public function stats(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius' => ['nullable', 'numeric', 'min:1', 'max:50'],
        ]);

        $radius = $validated['radius'] ?? 10;

        $incidents = TrafficIncident::active()
            ->nearby($validated['latitude'], $validated['longitude'], $radius)
            ->get();

        $stats = [
            'total_incidents' => $incidents->count(),
            'by_type' => $incidents->groupBy('type')->map->count(),
            'by_severity' => $incidents->groupBy('severity')->map->count(),
            'severe_count' => $incidents->where('severity', 'severe')->count(),
            'high_count' => $incidents->where('severity', 'high')->count(),
            'traffic_level' => $this->calculateTrafficLevel($incidents),
        ];

        return $this->success($stats, 'Statistiques de trafic.');
    }

    /**
     * Calculate expiration time based on incident type
     */
    private function calculateExpiration(string $type, ?float $customHours): \Carbon\Carbon
    {
        if ($customHours) {
            return now()->addHours($customHours);
        }

        return match ($type) {
            'congestion' => now()->addHours(1),
            'accident' => now()->addHours(3),
            'road_work' => now()->addHours(8),
            'road_closed' => now()->addHours(24),
            'police' => now()->addMinutes(30),
            'hazard' => now()->addHours(2),
            default => now()->addHours(2),
        };
    }

    /**
     * Calculate overall traffic level
     */
    private function calculateTrafficLevel($incidents): string
    {
        if ($incidents->isEmpty()) {
            return 'fluide';
        }

        $severeCount = $incidents->whereIn('severity', ['severe', 'high'])->count();
        $totalCount = $incidents->count();

        if ($severeCount >= 3 || $totalCount >= 10) {
            return 'très_dense';
        }

        if ($severeCount >= 1 || $totalCount >= 5) {
            return 'dense';
        }

        if ($totalCount >= 2) {
            return 'modéré';
        }

        return 'fluide';
    }
}
