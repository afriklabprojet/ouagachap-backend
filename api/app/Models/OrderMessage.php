<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'sender_id',
        'sender_type',
        'message',
        'image_url',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    // ==================== RELATIONSHIPS ====================

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    // ==================== SCOPES ====================

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    public function scopeFromClient($query)
    {
        return $query->where('sender_type', 'client');
    }

    public function scopeFromCourier($query)
    {
        return $query->where('sender_type', 'courier');
    }

    // ==================== HELPERS ====================

    public function markAsRead(): void
    {
        $this->update(['is_read' => true]);
    }

    public function isFromClient(): bool
    {
        return $this->sender_type === 'client';
    }

    public function isFromCourier(): bool
    {
        return $this->sender_type === 'courier';
    }

    // ==================== ACCESSORS ====================

    public function getIsFromClientAttribute(): bool
    {
        return $this->isFromClient();
    }

    public function getIsFromCourierAttribute(): bool
    {
        return $this->isFromCourier();
    }

    public function getSenderNameAttribute(): string
    {
        if ($this->relationLoaded('sender') && $this->sender) {
            return $this->sender->name;
        }

        return $this->sender_type === 'courier' ? 'Coursier' : 'Client';
    }
}
