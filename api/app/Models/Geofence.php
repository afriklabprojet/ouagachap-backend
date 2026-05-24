<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Geofence extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'coordinates',
        'type',
        'surge_multiplier',
        'is_active',
    ];

    protected $casts = [
        'coordinates' => 'array',
        'surge_multiplier' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // ==================== RELATIONSHIPS ====================

    public function logs(): HasMany
    {
        return $this->hasMany(GeofenceLog::class);
    }

    // ==================== SCOPES ====================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAllowed($query)
    {
        return $query->where('type', 'allowed');
    }

    public function scopeRestricted($query)
    {
        return $query->where('type', 'restricted');
    }

    public function scopeSurge($query)
    {
        return $query->where('type', 'surge');
    }

    // ==================== HELPERS ====================

    public function isAllowed(): bool
    {
        return $this->type === 'allowed';
    }

    public function isRestricted(): bool
    {
        return $this->type === 'restricted';
    }

    public function isSurge(): bool
    {
        return $this->type === 'surge';
    }

    /**
     * Check if a point is inside this geofence using ray-casting algorithm.
     */
    public function containsPoint(float $lat, float $lng): bool
    {
        $coordinates = $this->coordinates ?? [];
        $n = count($coordinates);

        if ($n < 3) {
            return false;
        }

        $inside = false;

        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            $yi = $coordinates[$i]['lat'] ?? 0;
            $xi = $coordinates[$i]['lng'] ?? 0;
            $yj = $coordinates[$j]['lat'] ?? 0;
            $xj = $coordinates[$j]['lng'] ?? 0;

            if ((($yi > $lat) !== ($yj > $lat))
                && ($lng < ($xj - $xi) * ($lat - $yi) / ($yj - $yi) + $xi)) {
                $inside = !$inside;
            }
        }

        return $inside;
    }

    public function getTypeLabel(): string
    {
        return match ($this->type) {
            'allowed' => '✅ Zone autorisée',
            'restricted' => '🚫 Zone restreinte',
            'surge' => '📈 Zone de surge',
            default => $this->type,
        };
    }

    public function getTypeColor(): string
    {
        return match ($this->type) {
            'allowed' => 'success',
            'restricted' => 'danger',
            'surge' => 'warning',
            default => 'secondary',
        };
    }
}
