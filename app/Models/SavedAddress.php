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

    // ========== RELATIONS ==========

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ========== SCOPES ==========

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // ========== METHODS ==========

    /**
     * Set this address as default and unset others
     */
    public function setAsDefault(): void
    {
        // Unset other default addresses for this user
        static::where('user_id', $this->user_id)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        // Set this one as default
        $this->update(['is_default' => true]);
    }

    /**
     * Get icon name based on type
     */
    public function getIconAttribute(): string
    {
        return match ($this->type) {
            'home' => 'home',
            'work' => 'work',
            default => 'location_on',
        };
    }

    /**
     * Get formatted label with icon
     */
    public function getDisplayLabelAttribute(): string
    {
        $emoji = match ($this->type) {
            'home' => '🏠',
            'work' => '🏢',
            default => '📍',
        };
        
        return "{$emoji} {$this->label}";
    }
}
