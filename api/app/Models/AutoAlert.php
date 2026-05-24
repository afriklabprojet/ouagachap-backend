<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AutoAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'trigger_type',
        'conditions',
        'actions',
        'is_active',
        'cooldown_minutes',
        'last_triggered_at',
    ];

    protected $casts = [
        'conditions' => 'array',
        'actions' => 'array',
        'is_active' => 'boolean',
        'cooldown_minutes' => 'integer',
        'last_triggered_at' => 'datetime',
    ];

    // ==================== SCOPES ====================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * @codeCoverageIgnore MySQL TIMESTAMPDIFF not compatible with SQLite
     */
    public function scopeReadyToTrigger($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('last_triggered_at')
                  ->orWhereRaw('TIMESTAMPDIFF(MINUTE, last_triggered_at, NOW()) >= cooldown_minutes');
            });
    }

    // ==================== HELPERS ====================

    public function isOnCooldown(): bool
    {
        if (!$this->last_triggered_at) {
            return false;
        }

        return $this->last_triggered_at->diffInMinutes(now()) < $this->cooldown_minutes;
    }

    public function markAsTriggered(): void
    {
        $this->update(['last_triggered_at' => now()]);
    }

    public function getTriggerTypeLabel(): string
    {
        return match ($this->trigger_type) {
            'order_delayed' => '⏰ Commande en retard',
            'courier_offline' => '📴 Coursier hors ligne',
            'low_couriers' => '👥 Peu de coursiers disponibles',
            'high_pending_orders' => '📦 Beaucoup de commandes en attente',
            'withdrawal_pending' => '💸 Retraits en attente',
            'negative_rating' => '⭐ Avis négatif',
            'zone_missing' => '🗺️ Commande hors zone tarifaire',
            default => $this->trigger_type,
        };
    }

    public function canTrigger(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        return !$this->isOnCooldown();
    }
}
