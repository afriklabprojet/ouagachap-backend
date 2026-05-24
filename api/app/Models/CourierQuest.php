<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $key
 * @property string $title
 * @property string $description
 * @property string $icon
 * @property string $quest_type
 * @property int $target_value
 * @property int $bonus_xof
 * @property bool $is_active
 */
class CourierQuest extends Model
{
    protected $fillable = [
        'key',
        'title',
        'description',
        'icon',
        'quest_type',
        'target_value',
        'bonus_xof',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'target_value' => 'integer',
            'bonus_xof'    => 'integer',
            'is_active'    => 'boolean',
        ];
    }

    public function progress(): HasMany
    {
        return $this->hasMany(CourierQuestProgress::class, 'quest_id');
    }

    public function scopeActive($query): void
    {
        $query->where('is_active', true);
    }
}
