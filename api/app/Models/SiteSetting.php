<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SiteSetting extends Model
{
    use HasFactory;

    public const TYPE_TEXT = 'text';
    public const TYPE_TEXTAREA = 'textarea';
    public const TYPE_IMAGE = 'image';
    public const TYPE_NUMBER = 'number';
    public const TYPE_JSON = 'json';
    public const TYPE_BOOLEAN = 'boolean';

    public const GROUP_GENERAL = 'general';
    public const GROUP_HERO = 'hero';
    public const GROUP_FEATURES = 'features';
    public const GROUP_PRICING = 'pricing';
    public const GROUP_TESTIMONIALS = 'testimonials';
    public const GROUP_CONTACT = 'contact';
    public const GROUP_SEO = 'seo';
    public const GROUP_SOCIAL = 'social';
    public const GROUP_APP_COURIER = 'app_courier';
    public const GROUP_DISPATCH = 'dispatch';
    public const GROUP_WALLET = 'wallet';

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'label',
        'description',
    ];

    // ==================== SCOPES ====================

    public function scopeOfGroup($query, string $group)
    {
        return $query->where('group', $group);
    }

    // ==================== STATIC HELPERS ====================

    /**
     * Get a setting value by key (cached for 10 minutes).
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("site_setting:{$key}", 600, function () use ($key, $default) {
            $setting = self::where('key', $key)->first();
            return $setting ? $setting->getCastValue() : $default;
        });
    }

    /**
     * Set a setting value by key.
     */
    public static function set(string $key, mixed $value, string $type = self::TYPE_TEXT, string $group = self::GROUP_GENERAL): void
    {
        $storeValue = is_array($value) ? json_encode($value) : (string) $value;

        self::updateOrCreate(
            ['key' => $key],
            ['value' => $storeValue, 'type' => $type, 'group' => $group]
        );
        Cache::forget("site_setting:{$key}");
    }

    /**
     * Clear all site setting caches.
     */
    public static function clearCache(): void
    {
        $settings = self::all();
        foreach ($settings as $setting) {
            Cache::forget("site_setting:{$setting->key}");
        }
    }

    /**
     * Get all settings as a key=>value array.
     */
    public static function getAll(): array
    {
        return self::all()->mapWithKeys(function ($item) {
            return [$item->key => $item->getCastValue()];
        })->toArray();
    }

    /**
     * Get all settings as a key=>value array for a given group.
     */
    public static function getGroup(string $group): array
    {
        return self::ofGroup($group)->get()->mapWithKeys(function ($item) {
            return [$item->key => $item->getCastValue()];
        })->toArray();
    }

    /**
     * Return value cast to the appropriate PHP type.
     */
    public function getCastValue(): mixed
    {
        return match ($this->type) {
            'boolean' => (bool) $this->value,
            'integer' => (int) $this->value,
            'float', 'number' => (float) $this->value,
            'json', 'array' => json_decode($this->value, true),
            default => $this->value,
        };
    }

    /**
     * Get group labels.
     */
    public static function getGroupLabels(): array
    {
        return [
            self::GROUP_GENERAL => 'Général',
            self::GROUP_HERO => 'Section Hero',
            self::GROUP_FEATURES => 'Fonctionnalités',
            self::GROUP_PRICING => 'Tarification',
            self::GROUP_TESTIMONIALS => 'Témoignages',
            self::GROUP_CONTACT => 'Contact',
            self::GROUP_SEO => 'SEO',
            self::GROUP_SOCIAL => 'Réseaux sociaux',
            self::GROUP_APP_COURIER => 'App Coursier',
            self::GROUP_DISPATCH => 'Dispatch & Affectation',
            self::GROUP_WALLET => 'Portefeuille & Retraits',
        ];
    }
}
