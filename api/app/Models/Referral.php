<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Referral extends Model
{
    use HasFactory;

    protected $fillable = [
        'referrer_id',
        'referred_id',
        'referrer_rewarded_at',
        'referred_rewarded_at',
    ];

    protected $casts = [
        'referrer_rewarded_at' => 'datetime',
        'referred_rewarded_at' => 'datetime',
    ];

    // ==================== RELATIONSHIPS ====================

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    public function referred(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_id');
    }

    // ==================== HELPERS ====================

    public function isReferrerRewarded(): bool
    {
        return $this->referrer_rewarded_at !== null;
    }

    public function isReferredRewarded(): bool
    {
        return $this->referred_rewarded_at !== null;
    }
}
