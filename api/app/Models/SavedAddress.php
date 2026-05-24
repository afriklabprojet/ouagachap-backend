<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavedAddress extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'label',
        'address',
        'latitude',
        'longitude',
        'contact_name',
        'contact_phone',
        'instructions',
        'is_default',
        'type',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'is_default' => 'boolean',
    ];

    // ==================== RELATIONSHIPS ====================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ==================== SCOPES ====================

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // ==================== ACCESSORS ====================

    public function getIconAttribute(): string
    {
        return match ($this->type) {
            'home' => 'home',
            'work' => 'work',
            default => 'location_on',
        };
    }

    public function getDisplayLabelAttribute(): string
    {
        $emoji = match ($this->type) {
            'home' => '🏠',
            'work' => '🏢',
            default => '📍',
        };

        return "{$emoji} {$this->label}";
    }

    // ==================== HELPERS ====================

    /**
     * Set this address as default and unset others for the same user.
     */
    public function setAsDefault(): void
    {
        self::where('user_id', $this->user_id)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        $this->update(['is_default' => true]);
    }
}
