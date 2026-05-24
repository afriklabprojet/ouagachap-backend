<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\SubscriptionPlan;
use App\Http\Controllers\Controller;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function __construct(
        protected SubscriptionService $subscriptionService,
    ) {}

    /**
     * POST /api/v1/subscriptions
     * Souscrire au CHAP Pass Basic ou Premium.
     */
    public function subscribe(Request $request): JsonResponse
    {
        $data = $request->validate([
            'plan' => ['required', 'string', 'in:basic,premium'],
        ]);

        $plan = SubscriptionPlan::from($data['plan']);
        $subscription = $this->subscriptionService->subscribe($request->user(), $plan);

        return response()->json([
            'success' => true,
            'message' => "Abonnement {$plan->label()} activé avec succès.",
            'data'    => $this->formatSubscription($subscription),
        ], 201);
    }

    /**
     * GET /api/v1/subscriptions/current
     * Retourner l'abonnement actif du client.
     */
    public function current(Request $request): JsonResponse
    {
        $subscription = $this->subscriptionService->getActiveSubscription($request->user());

        if ($subscription === null) {
            return response()->json([
                'success' => true,
                'data'    => null,
                'message' => 'Aucun abonnement actif.',
            ]);
        }

        return response()->json([
            'success' => true,
            'data'    => $this->formatSubscription($subscription),
        ]);
    }

    /**
     * DELETE /api/v1/subscriptions/cancel
     * Annuler l'abonnement actif du client.
     */
    public function cancel(Request $request): JsonResponse
    {
        $cancelled = $this->subscriptionService->cancelActiveSubscription($request->user());

        if (! $cancelled) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun abonnement actif à annuler.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Abonnement annulé avec succès.',
        ]);
    }

    // ─────────────────────────── Helpers ────────────────────────────────── //

    private function formatSubscription(\App\Models\Subscription $sub): array
    {
        return [
            'id'                => $sub->id,
            'plan'              => $sub->plan->value,
            'label'             => $sub->plan->label(),
            'price_xof'         => $sub->price_xof,
            'discount_xof'      => $sub->discount_xof,
            'priority_dispatch' => $sub->priority_dispatch,
            'starts_at'         => $sub->starts_at->toIso8601String(),
            'ends_at'           => $sub->ends_at->toIso8601String(),
            'is_active'         => $sub->isActive(),
        ];
    }
}
