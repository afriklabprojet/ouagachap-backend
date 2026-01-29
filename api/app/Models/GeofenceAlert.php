<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeofenceAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'courier_id',
        'zone_id',
        'type',
        'latitude',
        'longitude',
        'distance_meters',
        'message',
        'is_read',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'distance_meters' => 'decimal:2',
        'is_read' => 'boolean',
    ];

    const TYPE_ENTER = 'enter';
    const TYPE_EXIT = 'exit';
    const TYPE_PROXIMITY_PICKUP = 'proximity_pickup';
    const TYPE_PROXIMITY_DELIVERY = 'proximity_delivery';
    const TYPE_OUT_OF_BOUNDS = 'out_of_bounds';

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function courier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'courier_id');
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    /**
     * Créer une alerte de proximité pickup (à 200m)
     */
    public static function createProximityPickup(Order $order, float $lat, float $lng, float $distance): self
    {
        return self::create([
            'order_id' => $order->id,
            'courier_id' => $order->courier_id,
            'type' => self::TYPE_PROXIMITY_PICKUP,
            'latitude' => $lat,
            'longitude' => $lng,
            'distance_meters' => $distance,
            'message' => "Vous êtes à " . round($distance) . "m du point de collecte",
        ]);
    }

    /**
     * Créer une alerte de proximité livraison (à 200m)
     */
    public static function createProximityDelivery(Order $order, float $lat, float $lng, float $distance): self
    {
        return self::create([
            'order_id' => $order->id,
            'courier_id' => $order->courier_id,
            'type' => self::TYPE_PROXIMITY_DELIVERY,
            'latitude' => $lat,
            'longitude' => $lng,
            'distance_meters' => $distance,
            'message' => "Vous êtes à " . round($distance) . "m du point de livraison",
        ]);
    }

    /**
     * Créer une alerte hors zone
     */
    public static function createOutOfBounds(Order $order, float $lat, float $lng): self
    {
        return self::create([
            'order_id' => $order->id,
            'courier_id' => $order->courier_id,
            'type' => self::TYPE_OUT_OF_BOUNDS,
            'latitude' => $lat,
            'longitude' => $lng,
            'message' => "Attention: Vous êtes hors de la zone de livraison",
        ]);
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeForCourier($query, int $courierId)
    {
        return $query->where('courier_id', $courierId);
    }
}
