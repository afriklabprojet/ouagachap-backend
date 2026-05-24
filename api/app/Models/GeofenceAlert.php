<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeofenceAlert extends Model
{
    use HasFactory;

    public const TYPE_PROXIMITY_PICKUP = 'proximity_pickup';
    public const TYPE_PROXIMITY_DELIVERY = 'proximity_delivery';
    public const TYPE_OUT_OF_BOUNDS = 'out_of_bounds';

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
        'latitude'         => 'decimal:8',
        'longitude'        => 'decimal:8',
        'distance_meters'  => 'decimal:2',
        'is_read'          => 'boolean',
    ];

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

    public function scopeForCourier($query, int $courierId)
    {
        return $query->where('courier_id', $courierId);
    }

    public function scopeForOrder($query, int $orderId)
    {
        return $query->where('order_id', $orderId);
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public static function createProximityPickup(Order $order, float $latitude, float $longitude, float $distanceMeters): static
    {
        return static::create([
            'order_id'         => $order->id,
            'courier_id'       => $order->courier_id,
            'type'             => self::TYPE_PROXIMITY_PICKUP,
            'latitude'         => $latitude,
            'longitude'        => $longitude,
            'distance_meters'  => $distanceMeters,
            'message'          => 'Vous approchez du point de collecte (' . round($distanceMeters) . 'm).',
        ]);
    }

    public static function createProximityDelivery(Order $order, float $latitude, float $longitude, float $distanceMeters): static
    {
        return static::create([
            'order_id'         => $order->id,
            'courier_id'       => $order->courier_id,
            'type'             => self::TYPE_PROXIMITY_DELIVERY,
            'latitude'         => $latitude,
            'longitude'        => $longitude,
            'distance_meters'  => $distanceMeters,
            'message'          => 'Vous approchez du point de livraison (' . round($distanceMeters) . 'm).',
        ]);
    }

    public static function createOutOfBounds(Order $order, float $latitude, float $longitude): static
    {
        return static::create([
            'order_id'         => $order->id,
            'courier_id'       => $order->courier_id,
            'type'             => self::TYPE_OUT_OF_BOUNDS,
            'latitude'         => $latitude,
            'longitude'        => $longitude,
            'distance_meters'  => 0,
            'message'          => 'Vous etes hors de la zone de livraison.',
        ]);
    }
}
