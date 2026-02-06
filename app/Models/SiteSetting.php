<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SiteSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'label',
        'description',
    ];

    protected $casts = [
        'value' => 'string',
    ];

    /**
     * Types de paramètres supportés
     */
    const TYPE_TEXT = 'text';
    const TYPE_TEXTAREA = 'textarea';
    const TYPE_RICHTEXT = 'richtext';
    const TYPE_NUMBER = 'number';
    const TYPE_IMAGE = 'image';
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_JSON = 'json';

    /**
     * Groupes de paramètres
     */
    const GROUP_GENERAL = 'general';
    const GROUP_HERO = 'hero';
    const GROUP_FEATURES = 'features';
    const GROUP_PRICING = 'pricing';
    const GROUP_TESTIMONIALS = 'testimonials';
    const GROUP_CONTACT = 'contact';
    const GROUP_SOCIAL = 'social';
    const GROUP_SEO = 'seo';

    /**
     * Récupérer une valeur par clé
     */
    public static function get(string $key, $default = null)
    {
        return Cache::remember("site_setting_{$key}", 3600, function () use ($key, $default) {
            $setting = self::where('key', $key)->first();
            
            if (!$setting) {
                return $default;
            }

            // Convertir selon le type
            return match($setting->type) {
                self::TYPE_BOOLEAN => (bool) $setting->value,
                self::TYPE_NUMBER => (float) $setting->value,
                self::TYPE_JSON => json_decode($setting->value, true),
                default => $setting->value,
            };
        });
    }

    /**
     * Définir une valeur
     */
    public static function set(string $key, $value, string $type = self::TYPE_TEXT, string $group = self::GROUP_GENERAL, ?string $label = null, ?string $description = null): self
    {
        // Convertir la valeur si nécessaire
        if ($type === self::TYPE_JSON && is_array($value)) {
            $value = json_encode($value);
        }
        
        $setting = self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'type' => $type,
                'group' => $group,
                'label' => $label ?? ucfirst(str_replace('_', ' ', $key)),
                'description' => $description,
            ]
        );

        // Invalider le cache
        Cache::forget("site_setting_{$key}");
        Cache::forget('site_settings_all');

        return $setting;
    }

    /**
     * Récupérer tous les paramètres d'un groupe
     */
    public static function getGroup(string $group): array
    {
        return Cache::remember("site_settings_group_{$group}", 3600, function () use ($group) {
            $settings = self::where('group', $group)->get();
            $result = [];
            
            foreach ($settings as $setting) {
                $result[$setting->key] = match($setting->type) {
                    self::TYPE_BOOLEAN => (bool) $setting->value,
                    self::TYPE_NUMBER => (float) $setting->value,
                    self::TYPE_JSON => json_decode($setting->value, true),
                    default => $setting->value,
                };
            }
            
            return $result;
        });
    }

    /**
     * Récupérer tous les paramètres
     */
    public static function getAll(): array
    {
        return Cache::remember('site_settings_all', 3600, function () {
            $settings = self::all();
            $result = [];
            
            foreach ($settings as $setting) {
                $result[$setting->key] = match($setting->type) {
                    self::TYPE_BOOLEAN => (bool) $setting->value,
                    self::TYPE_NUMBER => (float) $setting->value,
                    self::TYPE_JSON => json_decode($setting->value, true),
                    default => $setting->value,
                };
            }
            
            return $result;
        });
    }

    /**
     * Vider le cache des paramètres
     */
    public static function clearCache(): void
    {
        $settings = self::all();
        
        foreach ($settings as $setting) {
            Cache::forget("site_setting_{$setting->key}");
        }
        
        Cache::forget('site_settings_all');
        
        foreach ([self::GROUP_GENERAL, self::GROUP_HERO, self::GROUP_FEATURES, self::GROUP_PRICING, self::GROUP_TESTIMONIALS, self::GROUP_CONTACT, self::GROUP_SOCIAL, self::GROUP_SEO] as $group) {
            Cache::forget("site_settings_group_{$group}");
        }
    }

    /**
     * Labels des groupes pour l'affichage
     */
    public static function getGroupLabels(): array
    {
        return [
            self::GROUP_GENERAL => 'Général',
            self::GROUP_HERO => 'Section Hero',
            self::GROUP_FEATURES => 'Fonctionnalités',
            self::GROUP_PRICING => 'Tarifs',
            self::GROUP_TESTIMONIALS => 'Témoignages',
            self::GROUP_CONTACT => 'Contact',
            self::GROUP_SOCIAL => 'Réseaux Sociaux',
            self::GROUP_SEO => 'SEO',
        ];
    }
}
