<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'balance',
        'pending_balance',
        'total_earned',
        'total_withdrawn',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'pending_balance' => 'decimal:2',
        'total_earned' => 'decimal:2',
        'total_withdrawn' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function withdrawals(): HasMany
    {
        return $this->hasMany(Withdrawal::class);
    }

    /**
     * Créditer le portefeuille
     */
    public function credit(float $amount): void
    {
        $this->increment('balance', $amount);
        $this->increment('total_earned', $amount);
    }

    /**
     * Débiter le portefeuille pour un retrait
     */
    public function debit(float $amount): bool
    {
        if ($this->balance < $amount) {
            return false;
        }

        $this->decrement('balance', $amount);
        $this->increment('pending_balance', $amount);

        return true;
    }

    /**
     * Confirmer un retrait (après paiement effectif)
     */
    public function confirmWithdrawal(float $amount): void
    {
        $this->decrement('pending_balance', $amount);
        $this->increment('total_withdrawn', $amount);
    }

    /**
     * Annuler un retrait (retourner au solde)
     */
    public function cancelWithdrawal(float $amount): void
    {
        $this->decrement('pending_balance', $amount);
        $this->increment('balance', $amount);
    }

    /**
     * Solde disponible pour retrait
     */
    public function getAvailableBalanceAttribute(): float
    {
        return max(0, $this->balance);
    }
}
