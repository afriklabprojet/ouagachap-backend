<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

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

    // ==================== RELATIONSHIPS ====================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class, 'user_id', 'user_id');
    }

    public function withdrawals(): HasMany
    {
        return $this->hasMany(Withdrawal::class);
    }

    // ==================== ACCESSORS ====================

    /**
     * Solde disponible pour retrait (balance - pending_balance)
     */
    public function getAvailableBalanceAttribute(): float
    {
        return max(0, (float) $this->balance - (float) $this->pending_balance);
    }

    // ==================== BUSINESS METHODS ====================

    /**
     * Créditer le portefeuille (thread-safe via DB lock)
     */
    public function credit(float $amount): void
    {
        DB::transaction(function () use ($amount) {
            $wallet = self::lockForUpdate()->find($this->id);
            $wallet->balance += $amount;
            $wallet->total_earned += $amount;
            $wallet->save();
            $this->refresh();
        });
    }

    /**
     * Débiter le portefeuille (met en pending_balance)
     */
    public function debit(float $amount): void
    {
        DB::transaction(function () use ($amount) {
            $wallet = self::lockForUpdate()->find($this->id);

            if ($wallet->balance < $amount) {
                throw new \Exception('Solde insuffisant');
            }

            $wallet->pending_balance += $amount;
            $wallet->save();
            $this->refresh();
        });
    }

    /**
     * Annuler un retrait en attente (rembourser le pending)
     */
    public function cancelWithdrawal(float $amount): void
    {
        DB::transaction(function () use ($amount) {
            $wallet = self::lockForUpdate()->find($this->id);
            $wallet->pending_balance = max(0, $wallet->pending_balance - $amount);
            $wallet->save();
            $this->refresh();
        });
    }

    /**
     * Confirmer un retrait approuvé (déduire du balance + pending + total_withdrawn)
     */
    public function confirmWithdrawal(float $amount): void
    {
        DB::transaction(function () use ($amount) {
            $wallet = self::lockForUpdate()->find($this->id);
            $wallet->balance = max(0, $wallet->balance - $amount);
            $wallet->pending_balance = max(0, $wallet->pending_balance - $amount);
            $wallet->total_withdrawn += $amount;
            $wallet->save();
            $this->refresh();
        });
    }
}
