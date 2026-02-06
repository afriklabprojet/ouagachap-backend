<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class TrafficIncident extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'reporter_id',
        'type',
        'severity',
        'latitude',
        'longitude',
        'address',
        'description',
        'confirmations',
        'is_active',
        'expires_at',
        'resolved_at',
        'resolved_by',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'confirmations' => 'integer',
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    // Types d'incidents
    const TYPE_CONGESTION = 'congestion';
    const TYPE_ACCIDENT = 'accident';
    const TYPE_ROAD_WORK = 'road_work';
    const TYPE_ROAD_CLOSED = 'road_closed';
    const TYPE_POLICE = 'police';
    const TYPE_HAZARD = 'hazard';

    // Niveaux de sévérité
    const SEVERITY_LOW = 'low';
    const SEVERITY_MODERATE = 'moderate';
    const SEVERITY_HIGH = 'high';
    const SEVERITY_SEVERE = 'severe';

    public static function getTypes(): array
    {
        return [
            self::TYPE_CONGESTION => 'Bouchon',
            self::TYPE_ACCIDENT => 'Accident',
            self::TYPE_ROAD_WORK => 'Travaux',
            self::TYPE_ROAD_CLOSED => 'Route fermée',
            self::TYPE_POLICE => 'Contrôle police',
            self::TYPE_HAZARD => 'Danger',
        ];
    }

    public static function getSeverities(): array
    {
        return [
            self::SEVERITY_LOW => 'Léger',
            self::SEVERITY_MODERATE => 'Modéré',
            self::SEVERITY_HIGH => 'Important',
            self::SEVERITY_SEVERE => 'Sévère',
        ];
    }

    // ==================== RELATIONSHIPS ====================

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    // ==================== SCOPES ====================

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    public function scopeNearby(Builder $query, float $latitude, float $longitude, float $radiusKm = 5): Builder
    {
        // Formule Haversine pour calculer la distance
        $haversine = "(6371 * acos(cos(radians(?)) 
                     * cos(radians(latitude)) 
                     * cos(radians(longitude) - radians(?)) 
                     + sin(radians(?)) 
                     * sin(radians(latitude))))";

        return $query->selectRaw("*, {$haversine} AS distance", [$latitude, $longitude, $latitude])
            ->whereRaw("{$haversine} < ?", [$latitude, $longitude, $latitude, $radiusKm])
            ->orderBy('distance');
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    // ==================== HELPERS ====================

    public function getTypeLabel(): string
    {
        return self::getTypes()[$this->type] ?? $this->type;
    }

    public function getSeverityLabel(): string
    {
        return self::getSeverities()[$this->severity] ?? $this->severity;
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function confirm(): void
    {
        $this->increment('confirmations');
    }

    public function resolve(int $userId = null): void
    {
        $this->update([
            'is_active' => false,
            'resolved_at' => now(),
            'resolved_by' => $userId,
        ]);
    }
}
