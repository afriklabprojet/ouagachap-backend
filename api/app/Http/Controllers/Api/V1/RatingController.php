<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RatingController extends Controller
{
    /**
     * Liste des notes reçues par l'utilisateur connecté
     */
    public function received(Request $request): JsonResponse
    {
        $ratings = Rating::where('rated_type', User::class)
            ->where('rated_id', $request->user()->id)
            ->with(['rater:id,name,phone', 'order:id,tracking_number'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $ratings,
            'average' => Rating::where('rated_type', User::class)
                ->where('rated_id', $request->user()->id)
                ->avg('score'),
        ]);
    }

    /**
     * Liste des notes données par l'utilisateur connecté
     */
    public function given(Request $request): JsonResponse
    {
        $ratings = Rating::where('rater_type', User::class)
            ->where('rater_id', $request->user()->id)
            ->with(['rated:id,name,phone', 'order:id,tracking_number'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $ratings,
        ]);
    }

    /**
     * Noter un coursier (après livraison)
     */
    public function rateCourier(Request $request, Order $order): JsonResponse
    {
        // Vérifier que c'est le client de cette commande
        if ($order->client_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez noter que vos propres commandes.',
            ], 403);
        }

        // Vérifier que la commande est livrée
        if ($order->status->value !== 'delivered') {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez noter qu\'après la livraison.',
            ], 422);
        }

        // Vérifier qu'un coursier est assigné
        if (!$order->courier_id) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun coursier assigné à cette commande.',
            ], 422);
        }

        // Vérifier qu'on n'a pas déjà noté
        $existingRating = Rating::where('order_id', $order->id)
            ->where('rater_id', $request->user()->id)
            ->where('rater_type', User::class)
            ->first();

        if ($existingRating) {
            return response()->json([
                'success' => false,
                'message' => 'Vous avez déjà noté cette commande.',
            ], 422);
        }

        $validated = $request->validate([
            'score' => 'required|integer|between:1,5',
            'comment' => 'nullable|string|max:500',
            'tags' => 'nullable|array',
            'tags.*' => ['string', Rule::in(['rapide', 'professionnel', 'aimable', 'ponctuel', 'soigneux'])],
        ]);

        $rating = Rating::create([
            'order_id' => $order->id,
            'rater_type' => User::class,
            'rater_id' => $request->user()->id,
            'rated_type' => User::class,
            'rated_id' => $order->courier_id,
            'score' => $validated['score'],
            'comment' => $validated['comment'] ?? null,
            'tags' => $validated['tags'] ?? [],
            'is_visible' => true,
        ]);

        // Mettre à jour la note moyenne du coursier
        $this->updateAverageRating($order->courier_id);

        return response()->json([
            'success' => true,
            'message' => 'Merci pour votre évaluation !',
            'data' => $rating,
        ], 201);
    }

    /**
     * Noter un client (après livraison) - pour les coursiers
     */
    public function rateClient(Request $request, Order $order): JsonResponse
    {
        // Vérifier que c'est le coursier de cette commande
        if ($order->courier_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez noter que vos propres livraisons.',
            ], 403);
        }

        // Vérifier que la commande est livrée
        if ($order->status->value !== 'delivered') {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez noter qu\'après la livraison.',
            ], 422);
        }

        // Vérifier qu'on n'a pas déjà noté
        $existingRating = Rating::where('order_id', $order->id)
            ->where('rater_id', $request->user()->id)
            ->where('rater_type', User::class)
            ->first();

        if ($existingRating) {
            return response()->json([
                'success' => false,
                'message' => 'Vous avez déjà noté ce client.',
            ], 422);
        }

        $validated = $request->validate([
            'score' => 'required|integer|between:1,5',
            'comment' => 'nullable|string|max:500',
            'tags' => 'nullable|array',
            'tags.*' => ['string', Rule::in(['adresse_claire', 'disponible', 'courtois', 'pourboire'])],
        ]);

        $rating = Rating::create([
            'order_id' => $order->id,
            'rater_type' => User::class,
            'rater_id' => $request->user()->id,
            'rated_type' => User::class,
            'rated_id' => $order->client_id,
            'score' => $validated['score'],
            'comment' => $validated['comment'] ?? null,
            'tags' => $validated['tags'] ?? [],
            'is_visible' => false, // Notes de clients moins visibles
        ]);

        // Mettre à jour la note moyenne du client
        $this->updateAverageRating($order->client_id);

        return response()->json([
            'success' => true,
            'message' => 'Évaluation enregistrée.',
            'data' => $rating,
        ], 201);
    }

    /**
     * Statistiques de notation d'un utilisateur
     */
    public function stats(Request $request, ?User $user = null): JsonResponse
    {
        $targetUser = $user ?? $request->user();

        $stats = [
            'average_score' => Rating::where('rated_type', User::class)
                ->where('rated_id', $targetUser->id)
                ->avg('score'),
            'total_ratings' => Rating::where('rated_type', User::class)
                ->where('rated_id', $targetUser->id)
                ->count(),
            'distribution' => [],
            'top_tags' => [],
        ];

        // Distribution par note
        for ($i = 1; $i <= 5; $i++) {
            $stats['distribution'][$i] = Rating::where('rated_type', User::class)
                ->where('rated_id', $targetUser->id)
                ->where('score', $i)
                ->count();
        }

        // Tags les plus fréquents
        $allTags = Rating::where('rated_type', User::class)
            ->where('rated_id', $targetUser->id)
            ->whereNotNull('tags')
            ->pluck('tags')
            ->flatten()
            ->countBy()
            ->sortDesc()
            ->take(5);

        $stats['top_tags'] = $allTags->toArray();

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Mettre à jour la note moyenne stockée sur l'utilisateur
     */
    private function updateAverageRating(int $userId): void
    {
        $average = Rating::where('rated_type', User::class)
            ->where('rated_id', $userId)
            ->avg('score');

        User::where('id', $userId)->update([
            'average_rating' => round($average, 2),
        ]);
    }
}
