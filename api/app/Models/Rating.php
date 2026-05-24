<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rating extends Model
{
    use HasFactory;

    const TYPE_CLIENT_TO_COURIER = 'client_to_courier';
    const TYPE_COURIER_TO_CLIENT = 'courier_to_client';

    const POSITIVE_TAGS = [
        'rapide' => 'Rapide',
        'aimable' => 'Aimable',
        'professionnel' => 'Professionnel',
        'soigneux' => 'Soigneux',
        'ponctuel' => 'Ponctuel',
    ];

    const NEGATIVE_TAGS = [
        'lent' => 'Lent',
        'impoli' => 'Impoli',
        'negligent' => 'Négligent',
        'retard' => 'En retard',
    ];

    protected $fillable = [
        'order_id',
        'rater_id',
        'rated_id',
        'type',
        'rating',
        'comment',
        'tags',
        'is_visible',
    ];

    protected $casts = [
        'rating' => 'integer',
        'tags' => 'array',
        'is_visible' => 'boolean',
    ];

    // ==================== RELATIONSHIPS ====================

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function rater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rater_id');
    }

    public function rated(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rated_id');
    }

    // ==================== SCOPES ====================

    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }

    public function scopeClientToCourier($query)
    {
        return $query->where('type', self::TYPE_CLIENT_TO_COURIER);
    }

    public function scopeCourierToClient($query)
    {
        return $query->where('type', self::TYPE_COURIER_TO_CLIENT);
    }

    public function scopeForCourier($query)
    {
        return $query->where('type', self::TYPE_CLIENT_TO_COURIER);
    }

    public function scopeForClient($query)
    {
        return $query->where('type', self::TYPE_COURIER_TO_CLIENT);
    }

    // ==================== STATIC METHODS ====================

    public static function averageForUser(int $userId, ?string $type = null): ?float
    {
        $query = static::where('rated_id', $userId)->visible();

        if ($type) {
            $query->where('type', $type);
        }

        $avg = $query->avg('rating');

        return $avg !== null ? round((float) $avg, 2) : null;
    }

    public static function statsForUser(int $userId): array
    {
        $ratings = static::where('rated_id', $userId)->visible()->get();

        $count = $ratings->count();
        $average = $count > 0 ? round($ratings->avg('rating'), 2) : null;

        $distribution = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
        foreach ($ratings as $rating) {
            if (isset($distribution[$rating->rating])) {
                $distribution[$rating->rating]++;
            }
        }

        $tags = [];
        foreach ($ratings as $rating) {
            if (is_array($rating->tags)) {
                foreach ($rating->tags as $tag) {
                    $tags[$tag] = ($tags[$tag] ?? 0) + 1;
                }
            }
        }

        return [
            'average' => $average,
            'count' => $count,
            'distribution' => $distribution,
            'tags' => $tags,
        ];
    }
}
