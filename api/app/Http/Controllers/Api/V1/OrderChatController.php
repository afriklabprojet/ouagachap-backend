<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseController;
use App\Events\OrderMessageSent;
use App\Models\Order;
use App\Models\OrderMessage;
use App\Models\User;
use App\Services\CdnService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OrderChatController extends BaseController
{
    /**
     * Récupérer les informations du chat d'une commande
     */
    public function show(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();

        // Vérifier que l'utilisateur est le client ou le coursier de la commande
        if (!$this->canAccessOrderChat($order, $user)) {
            return $this->forbidden('Vous n\'avez pas accès à ce chat');
        }

        // Eager load client & courier to avoid N+1 queries
        $order->loadMissing(['client:id,name,phone,avatar', 'courier:id,name,phone,avatar']);

        $messages = $order->messages()
            ->with('sender:id,name,phone,avatar')
            ->orderBy('created_at', 'asc')
            ->get();

        $formattedMessages = $messages->map(fn($msg) => $this->formatMessage($msg));

        // Use already-loaded messages instead of extra queries
        $senderType = $order->client_id === $user->id ? 'courier' : 'client';
        $unreadCount = $messages->where('sender_type', $senderType)->where('is_read', false)->count();
        $lastMessageAt = $messages->last()?->created_at;

        return $this->success([
            'order_id' => $order->id,
            'order_uuid' => $order->id,
            'client_id' => $order->client_id,
            'client_name' => $order->client?->name ?? 'Client',
            'client_phone' => $order->client?->phone,
            'client_photo' => $order->client?->avatar_url,
            'courier_id' => $order->courier_id,
            'courier_name' => $order->courier?->name ?? 'Coursier',
            'courier_phone' => $order->courier?->phone,
            'courier_photo' => $order->courier?->avatar_url,
            'messages' => $formattedMessages,
            'unread_count' => $unreadCount,
            'last_message_at' => $lastMessageAt,
        ], 'Chat récupéré');
    }

    /**
     * Récupérer les messages d'une commande (paginé)
     */
    public function messages(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();

        if (!$this->canAccessOrderChat($order, $user)) {
            return $this->forbidden('Vous n\'avez pas accès à ce chat');
        }

        $perPage = $request->get('per_page', 20);
        $messages = $order->messages()
            ->with('sender:id,name,phone,avatar')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return $this->success([
            'messages' => $messages->items() ? collect($messages->items())->map(fn($msg) => $this->formatMessage($msg)) : [],
            'current_page' => $messages->currentPage(),
            'last_page' => $messages->lastPage(),
            'total' => $messages->total(),
        ], 'Messages récupérés');
    }

    /**
     * Envoyer un message
     */
    public function sendMessage(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();

        if (!$this->canAccessOrderChat($order, $user)) {
            return $this->forbidden('Vous n\'avez pas accès à ce chat');
        }

        // Vérifier que la commande est active
        if ($order->isCompleted() || $order->isCancelled()) {
            return $this->error('Cette commande est terminée, vous ne pouvez plus envoyer de messages', 400);
        }

        $validator = Validator::make($request->all(), [
            'message' => 'required_without:image|string|max:2000',
            'image' => 'required_without:message|image|max:5120', // 5MB max
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        // Déterminer le type d'expéditeur
        $senderType = $order->client_id === $user->id ? 'client' : 'courier';

        // Gérer l'upload d'image si présent
        $imageUrl = null;
        if ($request->hasFile('image')) {
            $imageUrl = app(CdnService::class)->upload($request->file('image'), 'order-chats/' . $order->id);
        }

        $message = OrderMessage::create([
            'order_id' => $order->id,
            'sender_id' => $user->id,
            'sender_type' => $senderType,
            'message' => $request->input('message', ''),
            'image_url' => $imageUrl,
            'is_read' => false,
        ]);

        $message->load('sender:id,name,phone,avatar');

        // Envoyer notification push au destinataire + broadcast temps réel
        event(new OrderMessageSent($message));

        return $this->success($this->formatMessage($message), 'Message envoyé');
    }

    /**
     * Marquer les messages comme lus
     */
    public function markAsRead(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();

        if (!$this->canAccessOrderChat($order, $user)) {
            return $this->forbidden('Vous n\'avez pas accès à ce chat');
        }

        // Marquer comme lus les messages de l'autre partie
        $senderType = $order->client_id === $user->id ? 'courier' : 'client';

        $order->messages()
            ->where('sender_type', $senderType)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return $this->success(null, 'Messages marqués comme lus');
    }

    /**
     * Vérifier si l'utilisateur peut accéder au chat de la commande
     */
    private function canAccessOrderChat(Order $order, User $user): bool
    {
        return $order->client_id === $user->id || $order->courier_id === $user->id;
    }

    /**
     * Compter les messages non lus pour l'utilisateur
     * @codeCoverageIgnore Private method - never called from tested endpoints
     */
    private function getUnreadCount(Order $order, User $user): int
    {
        $senderType = $order->client_id === $user->id ? 'courier' : 'client';

        return $order->messages()
            ->where('sender_type', $senderType)
            ->where('is_read', false)
            ->count();
    }

    /**
     * Formater un message pour la réponse
     */
    private function formatMessage(OrderMessage $message): array
    {
        return [
            'id' => $message->id,
            'order_id' => $message->order_id,
            'sender_id' => $message->sender_id,
            'sender_type' => $message->sender_type,
            'sender_name' => $message->sender?->name ?? ($message->sender_type === 'client' ? 'Client' : 'Coursier'),
            'message' => $message->message,
            'image_url' => $message->image_url,
            'is_read' => $message->is_read,
            'created_at' => $message->created_at->toIso8601String(),
            'is_courier' => $message->sender_type === 'courier',
        ];
    }
}
