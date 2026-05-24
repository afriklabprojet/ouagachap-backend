<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportChat extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'status',
        'subject',
        'last_message_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    // ==================== RELATIONSHIPS ====================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportMessage::class);
    }

    public function unreadMessages(): HasMany
    {
        return $this->hasMany(SupportMessage::class)
            ->where('is_admin', false)
            ->where('is_read', false);
    }

    // ==================== SCOPES ====================

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }

    // ==================== HELPERS ====================

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function close(): void
    {
        $this->update(['status' => 'closed']);
    }

    public function touchLastMessage(): void
    {
        $this->update(['last_message_at' => now()]);
    }

    /**
     * Count unread messages for the user (non-admin messages the admin hasn't read,
     * and admin messages the user hasn't read).
     */
    public function unreadCountFor(bool $isAdmin): int
    {
        return $this->messages()
            ->where('is_admin', !$isAdmin)
            ->where('is_read', false)
            ->count();
    }

    /**
     * Get unread non-admin messages.
     */
    public function getUnreadMessagesAttribute()
    {
        return $this->messages()
            ->where('is_admin', false)
            ->where('is_read', false)
            ->get();
    }

    /**
     * Get the last message in this chat.
     */
    public function lastMessage(): ?SupportMessage
    {
        return $this->messages()->latest()->first();
    }
}
