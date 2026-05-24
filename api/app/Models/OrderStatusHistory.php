<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderStatusHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'status',
        'previous_status',
        'changed_by',
        'note',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'status' => OrderStatus::class,
        'previous_status' => OrderStatus::class,
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    // ==================== RELATIONSHIPS ====================

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    public function changedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
