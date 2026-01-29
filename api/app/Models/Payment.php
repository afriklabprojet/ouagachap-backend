<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'order_id',
        'user_id',
        'amount',
        'method',
        'status',
        'phone_number',
        'provider_transaction_id',
        'provider_response',
        'paid_at',
        'failed_at',
        'failure_reason',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'method' => PaymentMethod::class,
            'status' => PaymentStatus::class,
            'paid_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Payment $payment) {
            if (empty($payment->transaction_id)) {
                $payment->transaction_id = self::generateTransactionId();
            }
        });
    }

    // ==================== RELATIONSHIPS ====================

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ==================== SCOPES ====================

    public function scopePending($query)
    {
        return $query->where('status', PaymentStatus::PENDING);
    }

    public function scopeSuccess($query)
    {
        return $query->where('status', PaymentStatus::SUCCESS);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', PaymentStatus::FAILED);
    }

    // ==================== HELPERS ====================

    public static function generateTransactionId(): string
    {
        return 'TXN' . now()->format('YmdHis') . strtoupper(substr(uniqid(), -6));
    }

    public function isPending(): bool
    {
        return $this->status === PaymentStatus::PENDING;
    }

    public function isSuccess(): bool
    {
        return $this->status === PaymentStatus::SUCCESS;
    }

    public function isFailed(): bool
    {
        return $this->status === PaymentStatus::FAILED;
    }

    public function markAsSuccess(string $providerTransactionId, ?string $providerResponse = null): void
    {
        $this->update([
            'status' => PaymentStatus::SUCCESS,
            'provider_transaction_id' => $providerTransactionId,
            'provider_response' => $providerResponse,
            'paid_at' => now(),
        ]);
    }

    public function markAsFailed(string $reason, ?string $providerResponse = null): void
    {
        $this->update([
            'status' => PaymentStatus::FAILED,
            'failure_reason' => $reason,
            'provider_response' => $providerResponse,
            'failed_at' => now(),
        ]);
    }
}
