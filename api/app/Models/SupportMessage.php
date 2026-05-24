<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'support_chat_id',
        'user_id',
        'message',
        'is_admin',
        'is_read',
    ];

    protected $casts = [
        'is_admin' => 'boolean',
        'is_read' => 'boolean',
    ];

    // ==================== RELATIONSHIPS ====================

    public function chat(): BelongsTo
    {
        return $this->belongsTo(SupportChat::class, 'support_chat_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ==================== SCOPES ====================

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeFromAdmin($query)
    {
        return $query->where('is_admin', true);
    }

    public function scopeFromUser($query)
    {
        return $query->where('is_admin', false);
    }

    // ==================== HELPERS ====================

    public function markAsRead(): void
    {
        $this->update(['is_read' => true]);
    }
}
