<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PromoCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'type',
        'value',
        'min_order_amount',
        'max_discount',
        'max_uses',
        'max_uses_per_user',
        'current_uses',
        'is_active',
        'first_order_only',
        'applicable_zones',
        'starts_at',
        'expires_at',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'max_discount' => 'decimal:2',
        'max_uses' => 'integer',
        'max_uses_per_user' => 'integer',
        'current_uses' => 'integer',
        'is_active' => 'boolean',
        'first_order_only' => 'boolean',
        'applicable_zones' => 'array',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    const TYPE_PERCENTAGE = 'percentage';
    const TYPE_FIXED = 'fixed';
    const TYPE_FREE_DELIVERY = 'free_delivery';

    public function usages(): HasMany
    {
        return $this->hasMany(PromoCodeUsage::class);
    }

    /**
     * Vérifier si le code est valide
     */
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->max_uses && $this->current_uses >= $this->max_uses) {
            return false;
        }

        return true;
    }

    /**
     * Vérifier si un utilisateur peut utiliser ce code
     */
    public function canBeUsedBy(User $user, ?float $orderAmount = null, ?int $zoneId = null): array
    {
        if (!$this->isValid()) {
            return ['valid' => false, 'message' => 'Ce code promo n\'est plus valide'];
        }

        // Vérifier l'utilisation par utilisateur
        $userUsages = $this->usages()->where('user_id', $user->id)->count();
        if ($userUsages >= $this->max_uses_per_user) {
            return ['valid' => false, 'message' => 'Vous avez déjà utilisé ce code promo'];
        }

        // Vérifier première commande uniquement
        if ($this->first_order_only) {
            $previousOrders = Order::where('client_id', $user->id)
                ->where('status', 'delivered')
                ->exists();

            if ($previousOrders) {
                return ['valid' => false, 'message' => 'Ce code est réservé aux nouvelles inscriptions'];
            }
        }

        // Vérifier montant minimum
        if ($this->min_order_amount && $orderAmount && $orderAmount < $this->min_order_amount) {
            return [
                'valid' => false,
                'message' => "Commande minimum de {$this->min_order_amount} FCFA requise"
            ];
        }

        // Vérifier zone applicable
        if ($this->applicable_zones && $zoneId) {
            if (!in_array($zoneId, $this->applicable_zones)) {
                return ['valid' => false, 'message' => 'Ce code n\'est pas valide pour cette zone'];
            }
        }

        return ['valid' => true, 'message' => 'Code promo valide'];
    }

    /**
     * Calculer la réduction
     */
    public function calculateDiscount(float $orderAmount, float $deliveryFee): float
    {
        switch ($this->type) {
            case self::TYPE_PERCENTAGE:
                $discount = $orderAmount * ($this->value / 100);
                if ($this->max_discount) {
                    $discount = min($discount, $this->max_discount);
                }
                return $discount;

            case self::TYPE_FIXED:
                return min($this->value, $orderAmount);

            case self::TYPE_FREE_DELIVERY:
                return $deliveryFee;

            default:
                return 0;
        }
    }

    /**
     * Appliquer le code promo à une commande
     */
    public function apply(User $user, Order $order, float $discount): PromoCodeUsage
    {
        $this->increment('current_uses');

        return PromoCodeUsage::create([
            'promo_code_id' => $this->id,
            'user_id' => $user->id,
            'order_id' => $order->id,
            'discount_applied' => $discount,
        ]);
    }

    /**
     * Scope: codes actifs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->where(function ($q) {
                $q->whereNull('max_uses')->orWhereRaw('current_uses < max_uses');
            });
    }
}
