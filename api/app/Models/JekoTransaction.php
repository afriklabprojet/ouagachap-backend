<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Stub model — JekoTransaction table replaced by sappay_transactions.
 * Kept to avoid autoload failures in legacy test files.
 * @deprecated Use SappayTransaction instead.
 */
class JekoTransaction extends Model
{
    use HasFactory;

    protected $table = 'jeko_transactions';

    protected $fillable = [
        'user_id', 'reference', 'jeko_id', 'jeko_transaction_id',
        'type', 'payment_method', 'amount', 'currency', 'fees',
        'status', 'metadata', 'executed_at', 'counterpart_identifier',
    ];

    protected $casts = [
        'metadata' => 'array',
        'executed_at' => 'datetime',
        'amount' => 'integer',
        'fees' => 'integer',
    ];

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
        return in_array($this->status, ['error', 'cancelled', 'expired']);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
