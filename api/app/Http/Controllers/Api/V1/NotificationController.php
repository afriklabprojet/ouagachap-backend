<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\InAppNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Notifications In-App
 *
 * APIs pour gérer les notifications dans l'application
 */
class NotificationController extends Controller
{
    /**
     * Liste des notifications
     *
     * Récupère les notifications de l'utilisateur connecté
     *
     * @authenticated
     * @queryParam unread_only boolean Afficher uniquement les non lues. Example: true
     * @queryParam type string Filtrer par type (order_status, payment, promo, system, wallet). Example: order_status
     * @queryParam per_page int Nombre par page (max 50). Example: 20
     *
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "current_page": 1,
     *     "data": [...],
     *     "unread_count": 5
     *   }
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $query = InAppNotification::where('user_id', $request->user()->id)
            ->recent(30)
            ->orderBy('created_at', 'desc');

        if ($request->boolean('unread_only')) {
            $query->unread();
        }

        if ($request->has('type')) {
            $query->ofType($request->type);
        }

        $perPage = min($request->integer('per_page', 20), 50);
        $notifications = $query->paginate($perPage);

        $unreadCount = InAppNotification::where('user_id', $request->user()->id)
            ->unread()
            ->count();

        return response()->json([
            'success' => true,
            'data' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Nombre de notifications non lues
     *
     * @authenticated
     * @response 200 {
     *   "success": true,
     *   "unread_count": 5
     * }
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = InAppNotification::where('user_id', $request->user()->id)
            ->unread()
            ->count();

        return response()->json([
            'success' => true,
            'unread_count' => $count,
        ]);
    }

    /**
     * Marquer une notification comme lue
     *
     * @authenticated
     * @urlParam notification string required UUID de la notification. Example: 550e8400-e29b-41d4-a716-446655440000
     */
    public function markAsRead(Request $request, string $notificationId): JsonResponse
    {
        $notification = InAppNotification::where('user_id', $request->user()->id)
            ->where('id', $notificationId)
            ->first();

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification non trouvée',
            ], 404);
        }

        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Notification marquée comme lue',
        ]);
    }

    /**
     * Marquer plusieurs notifications comme lues
     *
     * @authenticated
     * @bodyParam ids array required Liste des UUIDs. Example: ["550e8400-e29b-41d4-a716-446655440000"]
     */
    public function markManyAsRead(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'string|uuid',
        ]);

        // Vérifier que les notifications appartiennent à l'utilisateur
        $count = InAppNotification::where('user_id', $request->user()->id)
            ->whereIn('id', $validated['ids'])
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => "{$count} notification(s) marquée(s) comme lue(s)",
            'marked_count' => $count,
        ]);
    }

    /**
     * Marquer toutes les notifications comme lues
     *
     * @authenticated
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $count = InAppNotification::markAllAsReadForUser($request->user()->id);

        return response()->json([
            'success' => true,
            'message' => "Toutes les notifications ont été marquées comme lues",
            'marked_count' => $count,
        ]);
    }

    /**
     * Supprimer une notification
     *
     * @authenticated
     * @urlParam notification string required UUID de la notification.
     */
    public function destroy(Request $request, string $notificationId): JsonResponse
    {
        $notification = InAppNotification::where('user_id', $request->user()->id)
            ->where('id', $notificationId)
            ->first();

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification non trouvée',
            ], 404);
        }

        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notification supprimée',
        ]);
    }

    /**
     * Supprimer les notifications lues
     *
     * @authenticated
     */
    public function clearRead(Request $request): JsonResponse
    {
        $count = InAppNotification::where('user_id', $request->user()->id)
            ->read()
            ->delete();

        return response()->json([
            'success' => true,
            'message' => "{$count} notification(s) supprimée(s)",
            'deleted_count' => $count,
        ]);
    }
}
