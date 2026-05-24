<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    use HasFactory;

    protected $fillable = [
        'category',
        'question',
        'answer',
        'order',
        'is_active',
        'views',
    ];

    protected $casts = [
        'order' => 'integer',
        'is_active' => 'boolean',
        'views' => 'integer',
    ];

    // ==================== SCOPES ====================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOfCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    // ==================== ACCESSORS ====================

    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'general' => '📋 Général',
            'orders' => '📦 Commandes',
            'payment' => '💰 Paiement',
            'delivery' => '🚚 Livraison',
            'account' => '👤 Compte',
            'wallet' => '💳 Portefeuille',
            default => '❓ Autre',
        };
    }

    public function getCategoryIconAttribute(): string
    {
        return match ($this->category) {
            'general' => 'help-circle',
            'orders' => 'package',
            'payment' => 'credit-card',
            'delivery' => 'truck',
            'account' => 'user',
            'wallet' => 'wallet',
            default => 'help-circle',
        };
    }

    // ==================== HELPERS ====================

    public function incrementViews(): void
    {
        $this->increment('views');
    }

    public static function categories(): array
    {
        return [
            'general' => 'Général',
            'orders' => 'Commandes',
            'payment' => 'Paiement',
            'delivery' => 'Livraison',
            'account' => 'Compte',
            'wallet' => 'Portefeuille',
        ];
    }
}
