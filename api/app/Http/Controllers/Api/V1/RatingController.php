<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Order;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RatingController extends BaseController
{
    /**
     * Liste des notes reçues par l'utilisateur connecté
     */
    public function received(Request $request): JsonResponse
    {
        $ratings = Rating::where('rated_id', $request->user()->id)
            ->with(['rater:id,name,phone', 'order:id,order_number'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return $this->success([
            'ratings' => $ratings,
            'average' => Rating::where('rated_id', $request->user()->id)->avg('rating'),
        ]);
    }

    /**
     * Liste des notes données par l'utilisateur connecté
     */
    public function given(Request $request): JsonResponse
    {
        $ratings = Rating::where('rater_id', $request->user()->id)
            ->with(['rated:id,name,phone', 'order:id,order_number'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return $this->success([
            'ratings' => $ratings,
            'average' => Rating::where('rater_id', $request->user()->id)->avg('rating'),
        ]);
    }

    /**
     * Noter un coursier (après livraison)
     */
    public function rateCourier(Request $request, Order $order): JsonResponse
    {
        $errorResponse = $this->getRateCourierError($request, $order);

        if ($errorResponse instanceof JsonResponse) {
            return $errorResponse;
        }

        $validated = $request->validate([
            'score' => 'required|integer|between:1,5',
            'comment' => 'nullable|string|max:500',
            'tags' => 'nullable|array',
            'tags.*' => ['string', Rule::in(['rapide', 'professionnel', 'aimable', 'ponctuel', 'soigneux'])],
        ]);

        $rating = Rating::create([
            'order_id' => $order->id,
            'rater_id' => $request->user()->id,
            'rated_id' => $order->courier_id,
            'type' => Rating::TYPE_CLIENT_TO_COURIER,
            'rating' => $validated['score'],
            'comment' => $validated['comment'] ?? null,
            'tags' => $validated['tags'] ?? [],
            'is_visible' => true,
        ]);

        // Mettre à jour la note moyenne du coursier
        $this->updateAverageRating($order->courier_id);

        return $this->success($rating, 'Merci pour votre évaluation !', 201);
    }

    /**
     * Noter un client (après livraison) - pour les coursiers
     */
    public function rateClient(Request $request, Order $order): JsonResponse
    {
        $errorResponse = $this->getRateClientError($request, $order);

        if ($errorResponse instanceof JsonResponse) {
            return $errorResponse;
        }

        $validated = $request->validate([
            'score' => 'required|integer|between:1,5',
            'comment' => 'nullable|string|max:500',
            'tags' => 'nullable|array',
            'tags.*' => ['string', Rule::in(['adresse_claire', 'disponible', 'courtois', 'pourboire'])],
        ]);

        $rating = Rating::create([
            'order_id' => $order->id,
            'rater_id' => $request->user()->id,
            'rated_id' => $order->client_id,
            'type' => Rating::TYPE_COURIER_TO_CLIENT,
            'rating' => $validated['score'],
            'comment' => $validated['comment'] ?? null,
            'tags' => $validated['tags'] ?? [],
            'is_visible' => false, // Notes de clients moins visibles
        ]);

        // Mettre à jour la note moyenne du client
        $this->updateAverageRating($order->client_id);

        return $this->success($rating, 'Évaluation enregistrée.', 201);
    }

    /**
     * Statistiques de notation d'un utilisateur
     */
    public function stats(Request $request, ?User $user = null): JsonResponse
    {
        $targetUser = $user ?? $request->user();

        // Single query to get all ratings for this user
        $ratings = Rating::where('rated_id', $targetUser->id)
            ->get(['rating', 'tags']);

        $stats = [
            'average_score' => $ratings->avg('rating'),
            'total_ratings' => $ratings->count(),
            'distribution' => [],
            'top_tags' => [],
        ];

        // Distribution par note — computed from collection instead of 5 extra queries
        $grouped = $ratings->countBy('rating');
        for ($i = 1; $i <= 5; $i++) {
            $stats['distribution'][$i] = $grouped[$i] ?? 0;
        }

        // Tags les plus fréquents — computed from same collection
        $stats['top_tags'] = $ratings
            ->pluck('tags')
            ->filter()
            ->flatten()
            ->countBy()
            ->sortDesc()
            ->take(5)
            ->toArray();

        return $this->success($stats);
    }

    /**
     * Mettre à jour la note moyenne stockée sur l'utilisateur
     */
    private function updateAverageRating(int $userId): void
    {
        $average = Rating::where('rated_id', $userId)
            ->avg('rating');

        User::where('id', $userId)->update([
            'average_rating' => round($average, 2),
        ]);
    }

    private function getRateCourierError(Request $request, Order $order): ?JsonResponse
    {
        $response = null;

        if ($order->client_id !== $request->user()->id) {
            $response = $this->forbidden('Vous ne pouvez noter que vos propres commandes.');
        } elseif ($order->status->value !== 'delivered') {
            $response = $this->error('Vous ne pouvez noter qu\'après la livraison.', 422);
        } elseif (! $order->courier_id) {
            $response = $this->error('Aucun coursier assigné à cette commande.', 422);
        } elseif ($this->ratingExists($order, $request->user()->id, Rating::TYPE_CLIENT_TO_COURIER)) {
            $response = $this->error('Vous avez déjà noté cette commande.', 422);
        }

        return $response;
    }

    private function getRateClientError(Request $request, Order $order): ?JsonResponse
    {
        $response = null;

        if ($order->courier_id !== $request->user()->id) {
            $response = $this->forbidden('Vous ne pouvez noter que vos propres livraisons.');
        } elseif ($order->status->value !== 'delivered') {
            $response = $this->error('Vous ne pouvez noter qu\'après la livraison.', 422);
        } elseif ($this->ratingExists($order, $request->user()->id, Rating::TYPE_COURIER_TO_CLIENT)) {
            $response = $this->error('Vous avez déjà noté ce client.', 422);
        }

        return $response;
    }

    private function ratingExists(Order $order, int $raterId, string $type): bool
    {
        return Rating::where('order_id', $order->id)
            ->where('rater_id', $raterId)
            ->where('type', $type)
            ->exists();
    }
}
