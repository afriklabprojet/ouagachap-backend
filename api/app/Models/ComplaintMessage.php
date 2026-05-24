<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplaintMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'complaint_id',
        'user_id',
        'message',
        'is_admin',
        'is_read',
    ];

    protected $casts = [
        'is_admin' => 'boolean',
        'is_read'  => 'boolean',
    ];

    public function complaint(): BelongsTo
    {
        return $this->belongsTo(Complaint::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
