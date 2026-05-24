<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'subject',
        'message',
        'is_read',
        'admin_reply',
        'replied_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'replied_at' => 'datetime',
    ];

    // ==================== SCOPES ====================

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    // ==================== METHODS ====================

    public function markAsRead(): void
    {
        $this->update(['is_read' => true]);
    }

    public function reply(string $replyText): void
    {
        $this->update([
            'admin_reply' => $replyText,
            'replied_at' => now(),
            'is_read' => true,
        ]);
    }
}
