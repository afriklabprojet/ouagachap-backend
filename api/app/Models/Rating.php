<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rating extends Model
{
    use HasFactory;

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

    const TYPE_CLIENT_TO_COURIER = 'client_to_courier';
    const TYPE_COURIER_TO_CLIENT = 'courier_to_client';

    // Tags prédéfinis
    const POSITIVE_TAGS = [
        'rapide' => 'Rapide',
        'professionnel' => 'Professionnel',
        'aimable' => 'Aimable',
        'ponctuel' => 'Ponctuel',
        'soigneux' => 'Soigneux',
        'communicatif' => 'Bonne communication',
    ];

    const NEGATIVE_TAGS = [
        'lent' => 'Lent',
        'impoli' => 'Impoli',
        'retard' => 'En retard',
        'colis_abime' => 'Colis abîmé',
        'difficile_joindre' => 'Difficile à joindre',
    ];

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

    // Scopes
    public function scopeForCourier($query)
    {
        return $query->where('type', self::TYPE_CLIENT_TO_COURIER);
    }

    public function scopeForClient($query)
    {
        return $query->where('type', self::TYPE_COURIER_TO_CLIENT);
    }

    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }

    /**
     * Calculer la moyenne des notes pour un utilisateur
     */
    public static function averageForUser(int $userId, ?string $type = null): ?float
    {
        $query = self::where('rated_id', $userId)->visible();

        if ($type) {
            $query->where('type', $type);
        }

        $avg = $query->avg('rating');

        return $avg ? round($avg, 2) : null;
    }

    /**
     * Statistiques de notation pour un utilisateur
     */
    public static function statsForUser(int $userId, ?string $type = null): array
    {
        $query = self::where('rated_id', $userId)->visible();

        if ($type) {
            $query->where('type', $type);
        }

        $ratings = $query->get();

        if ($ratings->isEmpty()) {
            return [
                'average' => null,
                'count' => 0,
                'distribution' => [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0],
                'tags' => [],
            ];
        }

        // Distribution des notes
        $distribution = $ratings->groupBy('rating')
            ->map(fn ($group) => $group->count())
            ->toArray();

        // Fusionner avec les valeurs par défaut
        $distribution = array_replace([5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0], $distribution);
        krsort($distribution);

        // Tags les plus fréquents
        $allTags = $ratings->pluck('tags')->flatten()->filter()->countBy()->sortDesc()->take(5);

        return [
            'average' => round($ratings->avg('rating'), 2),
            'count' => $ratings->count(),
            'distribution' => $distribution,
            'tags' => $allTags->toArray(),
        ];
    }
}
