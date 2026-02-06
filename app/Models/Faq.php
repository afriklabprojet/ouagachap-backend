<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

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
        'is_active' => 'boolean',
        'order' => 'integer',
        'views' => 'integer',
    ];

    /**
     * Scope pour les FAQs actives
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope pour filtrer par catégorie
     */
    public function scopeCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    /**
     * Incrémenter le compteur de vues
     */
    public function incrementViews(): void
    {
        $this->increment('views');
    }

    /**
     * Obtenir le label de la catégorie
     */
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

    /**
     * Obtenir l'icône de la catégorie
     */
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

    /**
     * Liste des catégories disponibles
     */
    public static function categories(): array
    {
        return [
            'general' => '📋 Général',
            'orders' => '📦 Commandes',
            'payment' => '💰 Paiement',
            'delivery' => '🚚 Livraison',
            'account' => '👤 Compte',
            'wallet' => '💳 Portefeuille',
        ];
    }
}
