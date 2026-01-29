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
        'is_active' => 'boolean',
        'surge_multiplier' => 'decimal:2',
    ];

    public function logs(): HasMany
    {
        return $this->hasMany(GeofenceLog::class);
    }

    public function getTypeLabel(): string
    {
        return match ($this->type) {
            'allowed' => '✅ Zone autorisée',
            'restricted' => '🚫 Zone restreinte',
            'surge' => '📈 Zone surge',
        };
    }

    public function getTypeColor(): string
    {
        return match ($this->type) {
            'allowed' => 'success',
            'restricted' => 'danger',
            'surge' => 'warning',
        };
    }

    /**
     * Check if a point is inside this geofence polygon
     */
    public function containsPoint(float $latitude, float $longitude): bool
    {
        $coordinates = $this->coordinates ?? [];
        if (count($coordinates) < 3) return false;

        $inside = false;
        $n = count($coordinates);
        
        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            $xi = $coordinates[$i]['lat'] ?? 0;
            $yi = $coordinates[$i]['lng'] ?? 0;
            $xj = $coordinates[$j]['lat'] ?? 0;
            $yj = $coordinates[$j]['lng'] ?? 0;

            if ((($yi > $longitude) != ($yj > $longitude)) &&
                ($latitude < ($xj - $xi) * ($longitude - $yi) / ($yj - $yi) + $xi)) {
                $inside = !$inside;
            }
        }

        return $inside;
    }
}
