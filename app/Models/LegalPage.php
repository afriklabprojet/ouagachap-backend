<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class LegalPage extends Model
{
    protected $fillable = [
        'slug',
        'title',
        'content',
        'meta_title',
        'meta_description',
        'is_published',
        'order',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Slugs prédéfinis pour les pages légales
     */
    public const SLUG_TERMS = 'conditions-utilisation';
    public const SLUG_PRIVACY = 'politique-confidentialite';
    public const SLUG_LEGAL = 'mentions-legales';
    public const SLUG_FAQ = 'faq';

    /**
     * Récupérer une page par son slug
     */
    public static function findBySlug(string $slug): ?self
    {
        return Cache::remember("legal_page_{$slug}", 3600, function () use ($slug) {
            return static::where('slug', $slug)
                ->where('is_published', true)
                ->first();
        });
    }

    /**
     * Récupérer toutes les pages publiées pour le footer
     */
    public static function getPublishedPages(): array
    {
        return Cache::remember('legal_pages_published', 3600, function () {
            return static::where('is_published', true)
                ->orderBy('order')
                ->get()
                ->toArray();
        });
    }

    /**
     * Vider le cache après modification
     */
    protected static function booted(): void
    {
        static::saved(function ($page) {
            Cache::forget("legal_page_{$page->slug}");
            Cache::forget('legal_pages_published');
        });

        static::deleted(function ($page) {
            Cache::forget("legal_page_{$page->slug}");
            Cache::forget('legal_pages_published');
        });
    }

    /**
     * Obtenir le titre SEO
     */
    public function getSeoTitleAttribute(): string
    {
        return $this->meta_title ?: $this->title . ' - OUAGA CHAP';
    }

    /**
     * Obtenir la description SEO
     */
    public function getSeoDescriptionAttribute(): string
    {
        return $this->meta_description ?: strip_tags(substr($this->content, 0, 160)) . '...';
    }
}
