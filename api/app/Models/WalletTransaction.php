<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class WalletTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'transaction_id',
        'amount',
        'type',
        'method',
        'phone_number',
        'status',
        'provider_transaction_id',
        'provider_response',
        'completed_at',
        'failure_reason',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'completed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (WalletTransaction $transaction) {
            if (empty($transaction->transaction_id)) {
                $transaction->transaction_id = self::generateTransactionId();
            }
        });
    }

    // ==================== STATIC METHODS ====================

    public static function generateTransactionId(): string
    {
        return 'RECH-' . strtoupper(Str::random(12));
    }

    // ==================== RELATIONSHIPS ====================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ==================== SCOPES ====================

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeSuccess($query)
    {
        return $query->where('status', 'success');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeRecharges($query)
    {
        return $query->where('type', 'recharge');
    }

    public function scopeDebit($query)
    {
        return $query->where('type', 'debit');
    }

    // ==================== ACCESSORS ====================

    public function getMethodLabelAttribute(): string
    {
        return match ($this->method) {
            'orange_money' => 'Orange Money',
            'moov_money' => 'Moov Money',
            'wave' => 'Wave',
            'mtn_money' => 'MTN Money',
            'djamo' => 'Djamo',
            'cash' => 'Espèces',
            default => ucfirst($this->method ?? ''),
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'En attente',
            'success' => 'Réussi',
            'failed' => 'Échoué',
            default => $this->status,
        };
    }

    // ==================== HELPERS ====================

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isSuccess(): bool
    {
        return $this->status === 'success';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function markAsSuccess(): void
    {
        $this->update([
            'status' => 'success',
            'completed_at' => now(),
        ]);
    }

    public function markAsFailed(string $reason): void
    {
        $this->update([
            'status' => 'failed',
            'failure_reason' => $reason,
        ]);
    }
}
