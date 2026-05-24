<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    // ==================== RELATIONSHIPS ====================

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    // ==================== SCOPES ====================

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeResolved($query)
    {
        return $query->where('is_active', false)->whereNotNull('resolved_at');
    }

    public function scopeSevere($query)
    {
        return $query->whereIn('severity', ['high', 'severe']);
    }

    // ==================== HELPERS ====================

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function confirm(): void
    {
        $this->increment('confirmations');
    }

    public function resolve(int $userId): void
    {
        $this->update([
            'is_active' => false,
            'resolved_at' => now(),
            'resolved_by' => $userId,
        ]);
    }

    // ==================== STATIC METHODS ====================

    public static function getTypes(): array
    {
        return [
            'congestion' => 'Bouchon',
            'accident' => 'Accident',
            'road_work' => 'Travaux',
            'road_closed' => 'Route fermée',
            'police' => 'Contrôle de police',
            'hazard' => 'Danger',
        ];
    }

    public static function getSeverities(): array
    {
        return [
            'low' => 'Faible',
            'moderate' => 'Modéré',
            'high' => 'Élevé',
            'severe' => 'Sévère',
        ];
    }

    public function getTypeLabel(): string
    {
        $types = self::getTypes();
        return $types[$this->type] ?? $this->type;
    }

    public function getSeverityLabel(): string
    {
        $severities = self::getSeverities();
        return $severities[$this->severity] ?? $this->severity;
    }
}
