<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsDeliveryReport extends Model
{
    protected $fillable = [
        'message_id',
        'phone',
        'sender',
        'status_group',
        'status_name',
        'status_description',
        'error_code',
        'error_name',
        'error_description',
        'price',
        'currency',
        'callback_data',
        'sent_at',
        'done_at',
        'sms_count',
    ];

    protected function casts(): array
    {
        return [
            'price'    => 'decimal:4',
            'sent_at'  => 'datetime',
            'done_at'  => 'datetime',
        ];
    }

    /**
     * Le SMS a-t-il été livré ?
     */
    public function isDelivered(): bool
    {
        return $this->status_group === 'DELIVERED';
    }

    /**
     * Le SMS est-il en attente ?
     */
    public function isPending(): bool
    {
        return in_array($this->status_group, ['PENDING', 'ACCEPTED']);
    }

    /**
     * Le SMS a-t-il échoué ?
     */
    public function isFailed(): bool
    {
        return in_array($this->status_group, ['REJECTED', 'UNDELIVERABLE', 'EXPIRED']);
    }

    /**
     * Chercher par message Infobip.
     */
    public function scopeByMessageId($query, string $messageId)
    {
        return $query->where('message_id', $messageId);
    }

    /**
     * Chercher par numéro de téléphone.
     */
    public function scopeByPhone($query, string $phone)
    {
        return $query->where('phone', $phone);
    }

    /**
     * Filtrer par statut de livraison.
     */
    public function scopeDelivered($query)
    {
        return $query->where('status_group', 'DELIVERED');
    }

    /**
     * Filtrer par échecs.
     */
    public function scopeFailed($query)
    {
        return $query->whereIn('status_group', ['REJECTED', 'UNDELIVERABLE', 'EXPIRED']);
    }
}
