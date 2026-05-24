<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $courier_id
 * @property int $quest_id
 * @property int $current_value
 * @property bool $completed
 * @property bool $reward_claimed
 * @property \Carbon\Carbon|null $completed_at
 */
class CourierQuestProgress extends Model
{
    protected $fillable = [
        'courier_id',
        'quest_id',
        'current_value',
        'completed',
        'reward_claimed',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'current_value'  => 'integer',
            'completed'      => 'boolean',
            'reward_claimed' => 'boolean',
            'completed_at'   => 'datetime',
        ];
    }

    public function courier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'courier_id');
    }

    public function quest(): BelongsTo
    {
        return $this->belongsTo(CourierQuest::class, 'quest_id');
    }

    public function progressPercent(): float
    {
        if ($this->quest->target_value <= 0) {
            return 100.0;
        }

        return min(100.0, round(($this->current_value / $this->quest->target_value) * 100, 1));
    }
}
