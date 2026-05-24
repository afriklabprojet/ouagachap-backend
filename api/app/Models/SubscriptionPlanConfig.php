<?php

namespace App\Models;

use App\Enums\SubscriptionPlan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SubscriptionPlanConfig extends Model
{
    protected $fillable = [
        'plan',
        'label',
        'price_xof',
        'discount_xof',
        'priority_dispatch',
        'is_active',
        'description',
    ];

    protected $casts = [
        'plan'              => SubscriptionPlan::class,
        'priority_dispatch' => 'boolean',
        'is_active'         => 'boolean',
        'price_xof'         => 'integer',
        'discount_xof'      => 'integer',
    ];

    // ─────────────────────────── Cache ──────────────────────────────────── //

    private const CACHE_KEY = 'subscription_plan_configs';
    private const CACHE_TTL = 300; // 5 minutes

    /**
     * Récupère la config d'un plan depuis le cache (fallback sur les valeurs de l'Enum).
     */
    public static function getConfig(SubscriptionPlan $plan): array
    {
        $configs = Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return self::all()->keyBy(fn (self $c) => $c->plan->value);
        });

        /** @var self|null $config */
        $config = $configs->get($plan->value);

        if ($config) {
            return [
                'price_xof'         => $config->price_xof,
                'discount_xof'      => $config->discount_xof,
                'priority_dispatch' => $config->priority_dispatch,
                'label'             => $config->label,
                'description'       => $config->description,
                'is_active'         => $config->is_active,
            ];
        }

        // Fallback sur les valeurs codées dans l'Enum
        return [
            'price_xof'         => $plan->priceXof(),
            'discount_xof'      => $plan->discountXof(),
            'priority_dispatch' => $plan->hasPriorityDispatch(),
            'label'             => $plan->label(),
            'description'       => null,
            'is_active'         => true,
        ];
    }

    /**
     * Vide le cache après une modification depuis Filament.
     */
    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    // ─────────────────────────── Hooks ──────────────────────────────────── //

    protected static function booted(): void
    {
        static::saved(fn () => self::clearCache());
        static::deleted(fn () => self::clearCache());
    }
}
