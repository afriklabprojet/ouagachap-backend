<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SappayTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'invoice_id',
        'reference',
        'type',
        'payment_method',
        'payment_processor_id',
        'customer_msisdn',
        'amount',
        'currency',
        'status',
        'requires_otp',
        'webhook_payload',
        'metadata',
        'executed_at',
    ];

    protected $casts = [
        'amount'          => 'decimal:2',
        'requires_otp'    => 'boolean',
        'metadata'        => 'array',
        'webhook_payload' => 'array',
        'executed_at'     => 'datetime',
    ];

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

    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    public function scopeFailed($query)
    {
        return $query->whereIn('status', ['error', 'cancelled']);
    }

    public function scopeWalletRecharge($query)
    {
        return $query->where('type', 'wallet_recharge');
    }

    public function scopeOrderPayment($query)
    {
        return $query->where('type', 'order_payment');
    }

    public function scopeForUser($query, int $userId)
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
            'pending'   => 'En attente',
            'success'   => 'Réussi',
            'error'     => 'Échoué',
            'expired'   => 'Expiré',
            'cancelled' => 'Annulé',
            default     => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending'   => 'warning',
            'success'   => 'success',
            'error'     => 'danger',
            'expired'   => 'secondary',
            'cancelled' => 'secondary',
            default     => 'secondary',
        };
    }

    public function getPaymentMethodNameAttribute(): string
    {
        return config("sappay.payment_methods.{$this->payment_method}.name", ucfirst($this->payment_method));
    }

    public function getFormattedAmountAttribute(): string
    {
        return number_format((float) $this->amount, 0, ',', ' ') . ' ' . ($this->currency ?? 'XOF');
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

    public function isSuccessful(): bool
    {
        return $this->status === 'success';
    }

    public function isFailed(): bool
    {
        return in_array($this->status, ['error', 'cancelled', 'expired']);
    }

    public function markAsSuccess(array $webhookData = []): void
    {
        $this->update([
            'status'          => 'success',
            'webhook_payload' => $webhookData,
            'executed_at'     => now(),
        ]);
    }

    public function markAsError(string $reason = 'Paiement échoué'): void
    {
        $metadata = is_array($this->metadata) ? $this->metadata : [];
        $metadata['error_reason'] = $reason;

        $this->update([
            'status'      => 'error',
            'metadata'    => $metadata,
            'executed_at' => now(),
        ]);
    }

    public function markAsExpired(): void
    {
        $this->update([
            'status'      => 'expired',
            'executed_at' => now(),
        ]);
    }
}
