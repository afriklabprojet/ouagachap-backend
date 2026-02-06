<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Zone extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'base_price',
        'price_per_km',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'base_price' => 'decimal:2',
            'price_per_km' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    // ==================== RELATIONSHIPS ====================

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    // ==================== SCOPES ====================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ==================== HELPERS ====================

    public function calculatePrice(float $distanceKm): array
    {
        $distancePrice = $distanceKm * $this->price_per_km;
        $totalPrice = $this->base_price + $distancePrice;
        $commissionRate = 0.15; // 15% commission
        $commissionAmount = $totalPrice * $commissionRate;
        $courierEarnings = $totalPrice - $commissionAmount;

        return [
            'base_price' => round($this->base_price, 2),
            'distance_price' => round($distancePrice, 2),
            'total_price' => round($totalPrice, 2),
            'commission_amount' => round($commissionAmount, 2),
            'courier_earnings' => round($courierEarnings, 2),
        ];
    }
}
