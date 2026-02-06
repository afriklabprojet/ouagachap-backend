<?php

namespace App\Services;

use App\Models\Faq;
use App\Models\Zone;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Service de cache centralisé pour les données statiques
 */
class CacheService
{
    // TTL par défaut (1 heure)
    protected int $defaultTtl = 3600;
    
    // TTL court (5 minutes)
    protected int $shortTtl = 300;
    
    // TTL long (24 heures)
    protected int $longTtl = 86400;

    /**
     * Récupérer les zones actives (cachées)
     */
    public function getActiveZones(): Collection
    {
        return Cache::remember('zones:active', $this->defaultTtl, function () {
            return Zone::where('is_active', true)
                ->orderBy('name')
                ->get();
        });
    }

    /**
     * Récupérer une zone par ID (cachée)
     */
    public function getZone(int $id): ?Zone
    {
        return Cache::remember("zones:{$id}", $this->defaultTtl, function () use ($id) {
            return Zone::find($id);
        });
    }

    /**
     * Récupérer les FAQs actives (cachées)
     */
    public function getActiveFaqs(): Collection
    {
        return Cache::remember('faqs:active', $this->longTtl, function () {
            return Faq::where('is_active', true)
                ->orderBy('order')
                ->orderBy('category')
                ->get();
        });
    }

    /**
     * Récupérer les FAQs par catégorie (cachées)
     */
    public function getFaqsByCategory(string $category): Collection
    {
        return Cache::remember("faqs:category:{$category}", $this->longTtl, function () use ($category) {
            return Faq::where('is_active', true)
                ->where('category', $category)
                ->orderBy('order')
                ->get();
        });
    }

    /**
     * Récupérer la configuration générale (cachée)
     */
    public function getGeneralConfig(): array
    {
        return Cache::remember('config:general', $this->defaultTtl, function () {
            return [
                'app_name' => config('app.name', 'OUAGA CHAP'),
                'app_version' => '1.0.0',
                'support_phone' => '+22670000000',
                'support_email' => 'support@ouagachap.com',
                'support_whatsapp' => '+22670000000',
                'currency' => 'XOF',
                'country_code' => 'BF',
                'default_language' => 'fr',
                'commission_rate' => 0.15,
                'min_order_amount' => 500,
                'max_order_amount' => 100000,
                'order_expiry_minutes' => 30,
                'otp_expiry_minutes' => 5,
            ];
        });
    }

    /**
     * Récupérer les infos de contact (cachées)
     */
    public function getContactInfo(): array
    {
        return Cache::remember('config:contact', $this->longTtl, function () {
            return [
                'phone' => '+22670000000',
                'whatsapp' => '+22670000000',
                'email' => 'support@ouagachap.com',
                'address' => 'Ouagadougou, Burkina Faso',
                'hours' => '08h00 - 20h00',
                'social' => [
                    'facebook' => 'https://facebook.com/ouagachap',
                    'instagram' => 'https://instagram.com/ouagachap',
                    'twitter' => 'https://twitter.com/ouagachap',
                ],
            ];
        });
    }

    /**
     * Invalider le cache des zones
     */
    public function clearZonesCache(): void
    {
        Cache::forget('zones:active');
        // Invalider aussi les zones individuelles
        Zone::pluck('id')->each(fn($id) => Cache::forget("zones:{$id}"));
    }

    /**
     * Invalider le cache des FAQs
     */
    public function clearFaqsCache(): void
    {
        Cache::forget('faqs:active');
        // Invalider les catégories
        Faq::distinct()->pluck('category')->each(
            fn($cat) => Cache::forget("faqs:category:{$cat}")
        );
    }

    /**
     * Invalider le cache de configuration
     */
    public function clearConfigCache(): void
    {
        Cache::forget('config:general');
        Cache::forget('config:contact');
        Cache::forget('site_settings:all');
        Cache::forget('site_settings:landing');
    }

    /**
     * Récupérer tous les site settings (cachés)
     */
    public function getSiteSettings(): array
    {
        return Cache::remember('site_settings:all', $this->defaultTtl, function () {
            return \App\Models\SiteSetting::pluck('value', 'key')->toArray();
        });
    }

    /**
     * Récupérer les settings pour la landing page (cachés)
     */
    public function getLandingSettings(): array
    {
        return Cache::remember('site_settings:landing', $this->defaultTtl, function () {
            $settings = \App\Models\SiteSetting::pluck('value', 'key')->toArray();
            
            // Décoder les JSON
            foreach (['features', 'pricing_plans', 'testimonials'] as $key) {
                if (isset($settings[$key]) && is_string($settings[$key])) {
                    $settings[$key] = json_decode($settings[$key], true) ?? [];
                }
            }
            
            return $settings;
        });
    }

    /**
     * Invalider le cache des site settings
     */
    public function clearSiteSettingsCache(): void
    {
        Cache::forget('site_settings:all');
        Cache::forget('site_settings:landing');
    }

    /**
     * Invalider tout le cache applicatif
     */
    public function clearAll(): void
    {
        $this->clearZonesCache();
        $this->clearFaqsCache();
        $this->clearConfigCache();
        $this->clearSiteSettingsCache();
    }

    /**
     * Préchauffer le cache
     */
    public function warmUp(): void
    {
        $this->getActiveZones();
        $this->getActiveFaqs();
        $this->getGeneralConfig();
        $this->getContactInfo();
    }
}
