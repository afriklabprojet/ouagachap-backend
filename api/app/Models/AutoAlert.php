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
        'last_triggered_at' => 'datetime',
    ];

    public function getTriggerTypeLabel(): string
    {
        return match ($this->trigger_type) {
            'order_delayed' => '⏰ Commande en retard',
            'courier_offline' => '📴 Coursier hors ligne',
            'low_couriers' => '👥 Peu de coursiers disponibles',
            'high_pending_orders' => '📦 Beaucoup de commandes en attente',
            'withdrawal_pending' => '💸 Retraits en attente',
            'negative_rating' => '⭐ Avis négatif',
        };
    }

    public function canTrigger(): bool
    {
        if (!$this->is_active) return false;
        if (!$this->last_triggered_at) return true;
        
        return $this->last_triggered_at->addMinutes($this->cooldown_minutes)->isPast();
    }
}
