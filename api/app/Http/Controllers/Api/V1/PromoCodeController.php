<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\PromoCode;
use App\Models\PromoCodeUsage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PromoCodeController extends BaseController
{
    /**
     * Valider un code promo
     */
    public function validate(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|max:50',
            'order_amount' => 'required|numeric|min:0',
            'zone_id' => 'nullable|integer|exists:zones,id',
        ]);

        $promo = PromoCode::where('code', strtoupper($request->code))
            ->where('is_active', true)
            ->first();

        if (!$promo) {
            return $this->notFound('Code promo invalide.');
        }

        // Vérifier la validité
        $validation = $this->validatePromoCode($promo, $request->user(), $request->order_amount, $request->zone_id);

        if (!$validation['valid']) {
            return $this->error($validation['message'], 422);
        }

        // Calculer la réduction
        $discount = $promo->calculateDiscount($request->order_amount);

        return $this->success([
                'code' => $promo->code,
                'description' => $promo->description,
                'discount_type' => $promo->type,
                'discount_value' => $promo->value,
                'calculated_discount' => $discount,
                'final_amount' => max(0, $request->order_amount - $discount),
                'valid_until' => $promo->expires_at?->format('Y-m-d H:i:s'),
            ]);
    }

    /**
     * Appliquer un code promo à une commande
     */
    public function apply(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|max:50',
            'order_id' => 'required|uuid|exists:orders,id',
        ]);

        $promo = PromoCode::where('code', strtoupper($request->code))
            ->where('is_active', true)
            ->first();

        if (!$promo) {
            return $this->notFound('Code promo invalide.');
        }

        $order = \App\Models\Order::find($request->order_id);

        // Vérifier que c'est bien la commande du client
        if ($order->client_id !== $request->user()->id) {
            return $this->forbidden('Cette commande ne vous appartient pas.');
        }

        // Vérifier que la commande n'est pas déjà livrée/annulée
        if ($order->status === \App\Enums\OrderStatus::DELIVERED || $order->status === \App\Enums\OrderStatus::CANCELLED) {
            return $this->error('Cette commande ne peut plus être modifiée.', 422);
        }

        // Vérifier qu'aucun code promo n'est déjà appliqué (via usage table)
        $alreadyApplied = PromoCodeUsage::where('order_id', $order->id)->exists();
        if ($alreadyApplied) {
            return $this->error('Un code promo est déjà appliqué à cette commande.', 422);
        }

        // Valider le code promo
        $validation = $this->validatePromoCode($promo, $request->user(), $order->total_price, $order->zone_id);

        if (!$validation['valid']) {
            return $this->error($validation['message'], 422);
        }

        // Calculer la réduction
        $discount = $promo->calculateDiscount($order->total_price);

        // forceFill requis — total_price est dans $guarded (protection mass assignment)
        $order->forceFill(['total_price' => max(0, $order->total_price - $discount)])->save();

        // Enregistrer l'utilisation
        PromoCodeUsage::create([
            'promo_code_id' => $promo->id,
            'user_id' => $request->user()->id,
            'order_id' => $order->id,
            'discount_applied' => $discount,
        ]);

        // Incrémenter le compteur d'utilisation
        $promo->increment('current_uses');

        return $this->success([
                'discount_applied' => $discount,
                'new_total' => $order->fresh()->total_price,
            ], 'Code promo appliqué avec succès !');
    }

    /**
     * Lister les codes promo actifs disponibles pour l'utilisateur
     */
    public function available(Request $request): JsonResponse
    {
        $user = $request->user();

        $promoCodes = PromoCode::where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', now());
            })
            ->where(function ($q) {
                $q->whereNull('max_uses')
                    ->orWhereColumn('current_uses', '<', 'max_uses');
            })
            ->withCount(['usages as user_usage_count' => function ($query) use ($user) {
                $query->where('user_id', $user->id);
            }])
            ->get()
            ->filter(function ($promo) {
                // @codeCoverageIgnoreStart
                // Vérifier si l'utilisateur peut encore utiliser ce code
                if ($promo->max_uses_per_user) {
                    return (int) $promo->getAttribute('user_usage_count') < $promo->max_uses_per_user;
                }
                return true;
                // @codeCoverageIgnoreEnd
            })
            ->values();

        return $this->success($promoCodes->map(fn($p) => [
                'id' => $p->id,
                'code' => $p->code,
                'name' => $p->name,
                'description' => $p->description,
                'type' => $p->type,
                'value' => (float) $p->value,
                'min_order_amount' => (float) $p->min_order_amount,
                'max_discount' => $p->max_discount ? (float) $p->max_discount : null,
                'expires_at' => $p->expires_at?->format('Y-m-d'),
                'first_order_only' => $p->first_order_only,
            ]));
    }

    /**
     * Historique d'utilisation des codes promo par l'utilisateur
     */
    public function history(Request $request): JsonResponse
    {
        $usages = PromoCodeUsage::where('user_id', $request->user()->id)
            ->with(['promoCode:id,code,description', 'order:id,order_number,total_price'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return $this->success($usages);
    }

    /**
     * Valider les conditions d'un code promo
     */
    private function validatePromoCode(PromoCode $promo, $user, float $orderAmount, ?int $zoneId = null): array
    {
        // Vérifier les dates de validité
        if ($promo->starts_at && $promo->starts_at->isFuture()) {
            return ['valid' => false, 'message' => 'Ce code promo n\'est pas encore actif.'];
        }

        if ($promo->expires_at && $promo->expires_at->isPast()) {
            return ['valid' => false, 'message' => 'Ce code promo a expiré.'];
        }

        // Vérifier le nombre max d'utilisations global
        if ($promo->max_uses && $promo->current_uses >= $promo->max_uses) {
            return ['valid' => false, 'message' => 'Ce code promo a atteint sa limite d\'utilisation.'];
        }

        // Vérifier le nombre max d'utilisations par utilisateur
        if ($promo->max_uses_per_user) {
            $userUsages = PromoCodeUsage::where('promo_code_id', $promo->id)
                ->where('user_id', $user->id)
                ->count();

            if ($userUsages >= $promo->max_uses_per_user) {
                return ['valid' => false, 'message' => 'Vous avez déjà utilisé ce code promo le nombre maximum de fois.'];
            }
        }

        // Vérifier le montant minimum de commande
        if ($promo->min_order_amount && $orderAmount < $promo->min_order_amount) {
            return [
                'valid' => false,
                'message' => "Le montant minimum de commande est de {$promo->min_order_amount} FCFA.",
            ];
        }

        // Vérifier les restrictions de zone
        if ($promo->applicable_zones && count($promo->applicable_zones) > 0 && $zoneId) {
            if (!in_array($zoneId, $promo->applicable_zones)) {
                return ['valid' => false, 'message' => 'Ce code promo n\'est pas valide pour cette zone.'];
            }
        }

        // Vérifier first_order_only
        if ($promo->first_order_only) {
            $hasOrders = \App\Models\Order::where('client_id', $user->id)
                ->where('status', \App\Enums\OrderStatus::DELIVERED)
                ->exists();

            if ($hasOrders) {
                return ['valid' => false, 'message' => 'Ce code promo est réservé aux nouvelles commandes.'];
            }
        }

        return ['valid' => true, 'message' => 'Code valide'];
    }
}
