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

    // ==================== RELATIONS ====================

    /**
     * Commande associée
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Expéditeur du message
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    // ==================== SCOPES ====================

    /**
     * Messages du client
     */
    public function scopeFromClient($query)
    {
        return $query->where('sender_type', 'client');
    }

    /**
     * Messages du coursier
     */
    public function scopeFromCourier($query)
    {
        return $query->where('sender_type', 'courier');
    }

    /**
     * Messages non lus
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    // ==================== ACCESSEURS ====================

    /**
     * Vérifie si le message est du client
     */
    public function getIsFromClientAttribute(): bool
    {
        return $this->sender_type === 'client';
    }

    /**
     * Vérifie si le message est du coursier
     */
    public function getIsFromCourierAttribute(): bool
    {
        return $this->sender_type === 'courier';
    }

    /**
     * Nom de l'expéditeur
     */
    public function getSenderNameAttribute(): string
    {
        return $this->sender?->name ?? ($this->sender_type === 'client' ? 'Client' : 'Coursier');
    }
}
