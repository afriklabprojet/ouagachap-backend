<?php

namespace App\Models;

use App\Enums\SubscriptionPlan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Subscription extends Model
{
    protected $fillable = [
        'user_id',
        'plan',
        'price_xof',
        'discount_xof',
        'priority_dispatch',
        'starts_at',
        'ends_at',
        'cancelled_at',
    ];

    protected $casts = [
        'plan'              => SubscriptionPlan::class,
        'priority_dispatch' => 'boolean',
        'starts_at'         => 'datetime',
        'ends_at'           => 'datetime',
        'cancelled_at'      => 'datetime',
    ];

    // ─────────────────────────── Relations ──────────────────────────────── //

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ─────────────────────────── Scopes ─────────────────────────────────── //

    public function scopeActive($query)
    {
        return $query->whereNull('cancelled_at')
                     ->where('ends_at', '>', now());
    }

    // ─────────────────────────── Helpers ────────────────────────────────── //

    /** Vrai si l'abonnement est encore valide et non annulé */
    public function isActive(): bool
    {
        return $this->cancelled_at === null
            && $this->ends_at->isFuture();
    }
}
