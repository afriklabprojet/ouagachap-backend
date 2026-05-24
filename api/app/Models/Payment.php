<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

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
        'payment_type',
        'phone_number',
        'provider_transaction_id',
        'provider_response',
        'paid_at',
        'failed_at',
        'failure_reason',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'method' => PaymentMethod::class,
        'status' => PaymentStatus::class,
        'paid_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Payment $payment) {
            if (empty($payment->transaction_id)) {
                $payment->transaction_id = 'TXN-' . strtoupper(Str::random(12));
            }
            if (empty($payment->payment_type)) {
                $payment->payment_type = 'order_payment';
            }
        });
    }

    // ==================== RELATIONSHIPS ====================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
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

    // ==================== STATIC METHODS ====================

    public static function generateTransactionId(): string
    {
        return 'TXN-' . strtoupper(Str::random(12));
    }

    // ==================== HELPERS ====================

    public function isPending(): bool
    {
        $status = $this->status instanceof PaymentStatus ? $this->status : PaymentStatus::tryFrom($this->status);
        return $status === PaymentStatus::PENDING;
    }

    public function isSuccess(): bool
    {
        $status = $this->status instanceof PaymentStatus ? $this->status : PaymentStatus::tryFrom($this->status);
        return $status === PaymentStatus::SUCCESS;
    }

    public function isFailed(): bool
    {
        $status = $this->status instanceof PaymentStatus ? $this->status : PaymentStatus::tryFrom($this->status);
        return $status === PaymentStatus::FAILED;
    }

    public function markAsSuccess(string $providerTransactionId, ?string $providerResponse = null): void
    {
        $this->update([
            'status'                 => 'success',
            'provider_transaction_id'=> $providerTransactionId,
            'provider_response'      => $providerResponse,
            'paid_at'                => now(),
        ]);
    }

    public function markAsFailed(string $failureReason, ?string $providerResponse = null): void
    {
        $this->update([
            'status'            => 'failed',
            'failure_reason'    => $failureReason,
            'provider_response' => $providerResponse,
            'failed_at'         => now(),
        ]);
    }
}
