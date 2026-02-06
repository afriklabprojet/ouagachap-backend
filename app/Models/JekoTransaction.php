<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model pour les transactions JEKO (paiements Mobile Money)
 */
class JekoTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'jeko_id',
        'jeko_transaction_id',
        'reference',
        'type',
        'payment_method',
        'amount',
        'currency',
        'fees',
        'status',
        'redirect_url',
        'counterpart_label',
        'counterpart_identifier',
        'metadata',
        'webhook_payload',
        'executed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'fees' => 'decimal:2',
            'metadata' => 'array',
            'webhook_payload' => 'array',
            'executed_at' => 'datetime',
        ];
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

    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // ==================== ACCESSORS ====================

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'En attente',
            'success' => 'Réussi',
            'error' => 'Échoué',
            'expired' => 'Expiré',
            'cancelled' => 'Annulé',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'warning',
            'success' => 'success',
            'error' => 'danger',
            'expired' => 'secondary',
            'cancelled' => 'secondary',
            default => 'secondary',
        };
    }

    public function getPaymentMethodNameAttribute(): string
    {
        $methods = config('jeko.payment_methods', []);
        return $methods[$this->payment_method]['name'] ?? ucfirst($this->payment_method);
    }

    public function getPaymentMethodIconAttribute(): string
    {
        $methods = config('jeko.payment_methods', []);
        return $methods[$this->payment_method]['icon'] ?? '💳';
    }

    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 0, ',', ' ') . ' ' . $this->currency;
    }

    // ==================== METHODS ====================

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isSuccessful(): bool
    {
        return $this->status === 'success';
    }

    public function isFailed(): bool
    {
        return in_array($this->status, ['error', 'expired', 'cancelled']);
    }

    public function markAsSuccess(array $webhookData = []): void
    {
        $this->update([
            'status' => 'success',
            'webhook_payload' => $webhookData,
            'executed_at' => now(),
        ]);
    }

    public function markAsError(string $reason = null): void
    {
        $this->update([
            'status' => 'error',
            'metadata' => array_merge($this->metadata ?? [], ['error_reason' => $reason]),
        ]);
    }

    public function markAsExpired(): void
    {
        $this->update(['status' => 'expired']);
    }
}
