<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeofenceLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'geofence_id',
        'event',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    // ==================== RELATIONSHIPS ====================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function geofence(): BelongsTo
    {
        return $this->belongsTo(Geofence::class);
    }

    // ==================== SCOPES ====================

    public function scopeEntered($query)
    {
        return $query->where('event', 'entered');
    }

    public function scopeExited($query)
    {
        return $query->where('event', 'exited');
    }

    // ==================== HELPERS ====================

    public function getEventIcon(): string
    {
        return match ($this->event) {
            'entered' => '📍 Entrée',
            'exited' => '🚪 Sortie',
            default => $this->event,
        };
    }
}
