<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PromoCode;
use App\Models\PromoCodeUsage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PromoCodeController extends Controller
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
            return response()->json([
                'success' => false,
                'message' => 'Code promo invalide.',
            ], 404);
        }

        // Vérifier la validité
        $validation = $this->validatePromoCode($promo, $request->user(), $request->order_amount, $request->zone_id);

        if (!$validation['valid']) {
            return response()->json([
                'success' => false,
                'message' => $validation['message'],
            ], 422);
        }

        // Calculer la réduction
        $discount = $promo->calculateDiscount($request->order_amount);

        return response()->json([
            'success' => true,
            'data' => [
                'code' => $promo->code,
                'description' => $promo->description,
                'discount_type' => $promo->discount_type,
                'discount_value' => $promo->discount_value,
                'calculated_discount' => $discount,
                'final_amount' => max(0, $request->order_amount - $discount),
                'valid_until' => $promo->valid_until?->format('Y-m-d H:i:s'),
            ],
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
            return response()->json([
                'success' => false,
                'message' => 'Code promo invalide.',
            ], 404);
        }

        $order = \App\Models\Order::find($request->order_id);

        // Vérifier que c'est bien la commande du client
        if ($order->client_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Cette commande ne vous appartient pas.',
            ], 403);
        }

        // Vérifier que la commande n'est pas déjà payée
        if ($order->payment_status->value === 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Cette commande est déjà payée.',
            ], 422);
        }

        // Vérifier qu'aucun code promo n'est déjà appliqué
        if ($order->promo_code_id) {
            return response()->json([
                'success' => false,
                'message' => 'Un code promo est déjà appliqué à cette commande.',
            ], 422);
        }

        // Valider le code promo
        $validation = $this->validatePromoCode($promo, $request->user(), $order->total_price, $order->pickup_zone_id);

        if (!$validation['valid']) {
            return response()->json([
                'success' => false,
                'message' => $validation['message'],
            ], 422);
        }

        // Calculer la réduction
        $discount = $promo->calculateDiscount($order->total_price);

        // Appliquer à la commande
        $order->update([
            'promo_code_id' => $promo->id,
            'discount_amount' => $discount,
            'total_price' => max(0, $order->total_price - $discount),
        ]);

        // Enregistrer l'utilisation
        PromoCodeUsage::create([
            'promo_code_id' => $promo->id,
            'user_id' => $request->user()->id,
            'order_id' => $order->id,
            'discount_applied' => $discount,
        ]);

        // Incrémenter le compteur d'utilisation
        $promo->increment('used_count');

        return response()->json([
            'success' => true,
            'message' => 'Code promo appliqué avec succès !',
            'data' => [
                'discount_applied' => $discount,
                'new_total' => $order->fresh()->total_price,
            ],
        ]);
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
            ->get()
            ->filter(function ($promo) use ($user) {
                // Vérifier si l'utilisateur peut encore utiliser ce code
                if ($promo->max_uses_per_user) {
                    $userUsages = PromoCodeUsage::where('promo_code_id', $promo->id)
                        ->where('user_id', $user->id)
                        ->count();
                    return $userUsages < $promo->max_uses_per_user;
                }
                return true;
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => $promoCodes->map(fn($p) => [
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
            ]),
        ]);
    }

    /**
     * Historique d'utilisation des codes promo par l'utilisateur
     */
    public function history(Request $request): JsonResponse
    {
        $usages = PromoCodeUsage::where('user_id', $request->user()->id)
            ->with(['promoCode:id,code,description', 'order:id,tracking_number,total_price'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $usages,
        ]);
    }

    /**
     * Valider les conditions d'un code promo
     */
    private function validatePromoCode(PromoCode $promo, $user, float $orderAmount, ?int $zoneId = null): array
    {
        // Vérifier les dates de validité
        if ($promo->valid_from && $promo->valid_from->isFuture()) {
            return ['valid' => false, 'message' => 'Ce code promo n\'est pas encore actif.'];
        }

        if ($promo->valid_until && $promo->valid_until->isPast()) {
            return ['valid' => false, 'message' => 'Ce code promo a expiré.'];
        }

        // Vérifier le nombre max d'utilisations global
        if ($promo->max_uses && $promo->used_count >= $promo->max_uses) {
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
        if ($promo->zone_ids && count($promo->zone_ids) > 0 && $zoneId) {
            if (!in_array($zoneId, $promo->zone_ids)) {
                return ['valid' => false, 'message' => 'Ce code promo n\'est pas valide pour cette zone.'];
            }
        }

        // Vérifier first_order_only
        if ($promo->first_order_only) {
            $hasOrders = \App\Models\Order::where('client_id', $user->id)
                ->where('payment_status', 'paid')
                ->exists();

            if ($hasOrders) {
                return ['valid' => false, 'message' => 'Ce code promo est réservé aux nouvelles commandes.'];
            }
        }

        return ['valid' => true, 'message' => 'Code valide'];
    }
}
