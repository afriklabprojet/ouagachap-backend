<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseController;
use App\Models\Faq;
use App\Models\SupportChat;
use App\Models\SupportMessage;
use App\Models\Complaint;
use App\Models\ComplaintMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SupportController extends BaseController
{
    // ==================== INFORMATIONS DE CONTACT ====================

    /**
     * Obtenir les informations de contact et paramètres de support
     */
    public function contactInfo(): JsonResponse
    {
        return $this->success([
            'phone' => '+226 70 00 00 00',
            'phone_display' => '70 00 00 00',
            'email' => 'support@ouagachap.com',
            'whatsapp' => '+22670000000',
            'whatsapp_message' => 'Bonjour, j\'ai besoin d\'aide avec mon compte OUAGA CHAP.',
            'working_hours' => [
                'days' => 'Lundi - Samedi',
                'hours' => '07:00 - 20:00',
            ],
            'social' => [
                'facebook' => 'https://facebook.com/ouagachap',
                'instagram' => 'https://instagram.com/ouagachap',
                'twitter' => 'https://twitter.com/ouagachap',
            ],
            'address' => [
                'street' => 'Avenue Kwamé Nkrumah',
                'city' => 'Ouagadougou',
                'country' => 'Burkina Faso',
            ],
        ], 'Informations de contact récupérées');
    }

    // ==================== FAQ ====================

    /**
     * Liste des FAQs avec possibilité de filtrer par catégorie
     */
    public function faqs(Request $request): JsonResponse
    {
        $query = Faq::active()->orderBy('order');

        // Filtrer par catégorie si fourni
        if ($request->has('category') && $request->category !== 'all') {
            $query->category($request->category);
        }

        // Recherche dans les questions/réponses
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('question', 'like', "%{$search}%")
                    ->orWhere('answer', 'like', "%{$search}%");
            });
        }

        $faqs = $query->get()->map(function ($faq) {
            return [
                'id' => $faq->id,
                'category' => $faq->category,
                'category_label' => $faq->category_label,
                'category_icon' => $faq->category_icon,
                'question' => $faq->question,
                'answer' => $faq->answer,
                'views' => $faq->views,
            ];
        });

        // Grouper par catégorie si demandé
        if ($request->boolean('grouped')) {
            $grouped = $faqs->groupBy('category');
            return $this->success([
                'categories' => Faq::categories(),
                'faqs' => $grouped,
                'total' => $faqs->count(),
            ], 'FAQs récupérées');
        }

        return $this->success([
            'categories' => Faq::categories(),
            'faqs' => $faqs,
            'total' => $faqs->count(),
        ], 'FAQs récupérées');
    }

    /**
     * Incrémenter le compteur de vues d'une FAQ
     */
    public function viewFaq(int $id): JsonResponse
    {
        $faq = Faq::find($id);

        if (!$faq) {
            return $this->notFound('FAQ non trouvée');
        }

        $faq->incrementViews();

        return $this->success(['views' => $faq->views], 'Vue enregistrée');
    }

    // ==================== CHAT SUPPORT ====================

    /**
     * Obtenir ou créer une conversation de chat
     */
    public function getOrCreateChat(Request $request): JsonResponse
    {
        $user = $request->user();

        // Chercher une conversation ouverte existante
        $chat = SupportChat::where('user_id', $user->id)
            ->where('status', 'open')
            ->first();

        if (!$chat) {
            // Créer une nouvelle conversation
            $chat = SupportChat::create([
                'user_id' => $user->id,
                'status' => 'open',
                'subject' => $request->input('subject', 'Nouvelle conversation'),
            ]);
        }

        return $this->success($this->formatChat($chat), 'Conversation récupérée');
    }

    /**
     * Liste des conversations de l'utilisateur
     */
    public function chats(Request $request): JsonResponse
    {
        $user = $request->user();

        $chats = SupportChat::where('user_id', $user->id)
            ->with(['messages' => function ($query) {
                $query->latest()->limit(1);
            }])
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(fn($chat) => $this->formatChat($chat));

        return $this->success([
            'chats' => $chats,
            'has_open' => $chats->where('status', 'open')->isNotEmpty(),
        ], 'Conversations récupérées');
    }

    /**
     * Messages d'une conversation
     */
    public function chatMessages(Request $request, int $chatId): JsonResponse
    {
        $user = $request->user();

        $chat = SupportChat::where('user_id', $user->id)
            ->where('id', $chatId)
            ->first();

        if (!$chat) {
            return $this->notFound('Conversation non trouvée');
        }

        // Marquer les messages admin comme lus
        $chat->messages()->where('is_admin', true)->where('is_read', false)->update(['is_read' => true]);

        $messages = $chat->messages()
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn($msg) => $this->formatMessage($msg));

        return $this->success([
            'chat' => $this->formatChat($chat),
            'messages' => $messages,
        ], 'Messages récupérés');
    }

    /**
     * Envoyer un message dans une conversation
     */
    public function sendMessage(Request $request, int $chatId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:2000',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $user = $request->user();

        $chat = SupportChat::where('user_id', $user->id)
            ->where('id', $chatId)
            ->first();

        if (!$chat) {
            return $this->notFound('Conversation non trouvée');
        }

        if ($chat->status === 'closed') {
            return $this->error('Cette conversation est fermée', 400);
        }

        $message = SupportMessage::create([
            'support_chat_id' => $chat->id,
            'user_id' => $user->id,
            'message' => $request->message,
            'is_admin' => false,
            'is_read' => false,
        ]);

        // Mettre à jour le dernier message
        $chat->update(['last_message_at' => now()]);

        return $this->success($this->formatMessage($message), 'Message envoyé', 201);
    }

    /**
     * Fermer une conversation
     */
    public function closeChat(Request $request, int $chatId): JsonResponse
    {
        $user = $request->user();

        $chat = SupportChat::where('user_id', $user->id)
            ->where('id', $chatId)
            ->first();

        if (!$chat) {
            return $this->notFound('Conversation non trouvée');
        }

        $chat->update(['status' => 'closed']);

        return $this->success($this->formatChat($chat), 'Conversation fermée');
    }

    // ==================== RÉCLAMATIONS / TICKETS ====================

    /**
     * Liste des réclamations de l'utilisateur
     */
    public function complaints(Request $request): JsonResponse
    {
        $user = $request->user();

        $complaints = Complaint::where('user_id', $user->id)
            ->with(['order:id,tracking_number', 'messages' => function ($query) {
                $query->latest()->limit(1);
            }])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($complaint) => $this->formatComplaint($complaint));

        return $this->success([
            'complaints' => $complaints,
            'types' => $this->complaintTypes(),
        ], 'Réclamations récupérées');
    }

    /**
     * Détails d'une réclamation avec ses messages
     */
    public function complaintDetails(Request $request, int $complaintId): JsonResponse
    {
        $user = $request->user();

        $complaint = Complaint::where('user_id', $user->id)
            ->where('id', $complaintId)
            ->with(['order', 'messages.user'])
            ->first();

        if (!$complaint) {
            return $this->notFound('Réclamation non trouvée');
        }

        // Marquer les messages admin comme lus
        $complaint->messages()->where('is_admin', true)->where('is_read', false)->update(['is_read' => true]);

        $messages = $complaint->messages()
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn($msg) => $this->formatComplaintMessage($msg));

        return $this->success([
            'complaint' => $this->formatComplaint($complaint),
            'messages' => $messages,
        ], 'Détails réclamation récupérés');
    }

    /**
     * Créer une nouvelle réclamation
     */
    public function createComplaint(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|string|in:delivery_issue,payment_issue,courier_behavior,app_bug,other',
            'subject' => 'required|string|max:255',
            'description' => 'required|string|max:2000',
            'order_id' => 'nullable|exists:orders,id',
            'priority' => 'nullable|string|in:low,medium,high,urgent',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $user = $request->user();

        $complaint = Complaint::create([
            'user_id' => $user->id,
            'type' => $request->type,
            'subject' => $request->subject,
            'description' => $request->description,
            'order_id' => $request->order_id,
            'priority' => $request->input('priority', 'medium'),
            'status' => 'open',
        ]);

        // Créer le premier message avec la description
        ComplaintMessage::create([
            'complaint_id' => $complaint->id,
            'user_id' => $user->id,
            'message' => $request->description,
            'is_admin' => false,
            'is_read' => false,
        ]);

        return $this->success($this->formatComplaint($complaint), 'Réclamation créée avec succès', 201);
    }

    /**
     * Ajouter un message à une réclamation
     */
    public function addComplaintMessage(Request $request, int $complaintId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:2000',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $user = $request->user();

        $complaint = Complaint::where('user_id', $user->id)
            ->where('id', $complaintId)
            ->first();

        if (!$complaint) {
            return $this->notFound('Réclamation non trouvée');
        }

        if (in_array($complaint->status, ['resolved', 'closed'])) {
            return $this->error('Cette réclamation est fermée', 400);
        }

        $message = ComplaintMessage::create([
            'complaint_id' => $complaint->id,
            'user_id' => $user->id,
            'message' => $request->message,
            'is_admin' => false,
            'is_read' => false,
        ]);

        $complaint->touch();

        return $this->success($this->formatComplaintMessage($message), 'Message ajouté', 201);
    }

    // ==================== HELPERS ====================

    private function formatChat(SupportChat $chat): array
    {
        $lastMessage = $chat->messages()->latest()->first();
        $unreadCount = $chat->messages()->where('is_admin', true)->where('is_read', false)->count();

        return [
            'id' => $chat->id,
            'subject' => $chat->subject,
            'status' => $chat->status,
            'status_label' => $chat->status === 'open' ? 'Ouverte' : 'Fermée',
            'last_message' => $lastMessage ? [
                'text' => $lastMessage->message,
                'is_admin' => $lastMessage->is_admin,
                'created_at' => $lastMessage->created_at->toIso8601String(),
            ] : null,
            'unread_count' => $unreadCount,
            'last_message_at' => $chat->last_message_at?->toIso8601String(),
            'created_at' => $chat->created_at->toIso8601String(),
        ];
    }

    private function formatMessage(SupportMessage $message): array
    {
        return [
            'id' => $message->id,
            'message' => $message->message,
            'is_admin' => $message->is_admin,
            'is_read' => $message->is_read,
            'sender_name' => $message->is_admin ? 'Support OUAGA CHAP' : $message->user?->name,
            'created_at' => $message->created_at->toIso8601String(),
        ];
    }

    private function formatComplaint(Complaint $complaint): array
    {
        $lastMessage = $complaint->messages()->latest()->first();
        $unreadCount = $complaint->messages()->where('is_admin', true)->where('is_read', false)->count();

        return [
            'id' => $complaint->id,
            'ticket_number' => $complaint->ticket_number,
            'type' => $complaint->type,
            'type_label' => $complaint->getTypeLabel(),
            'subject' => $complaint->subject,
            'description' => $complaint->description,
            'status' => $complaint->status,
            'status_color' => $complaint->getStatusColor(),
            'status_label' => $this->getStatusLabel($complaint->status),
            'priority' => $complaint->priority,
            'priority_color' => $complaint->getPriorityColor(),
            'priority_label' => $this->getPriorityLabel($complaint->priority),
            'order_id' => $complaint->order_id,
            'order_tracking' => $complaint->order?->tracking_number,
            'resolution' => $complaint->resolution,
            'resolved_at' => $complaint->resolved_at?->toIso8601String(),
            'last_message' => $lastMessage ? [
                'text' => $lastMessage->message,
                'is_admin' => $lastMessage->is_admin,
                'created_at' => $lastMessage->created_at->toIso8601String(),
            ] : null,
            'unread_count' => $unreadCount,
            'created_at' => $complaint->created_at->toIso8601String(),
            'updated_at' => $complaint->updated_at->toIso8601String(),
        ];
    }

    private function formatComplaintMessage(ComplaintMessage $message): array
    {
        return [
            'id' => $message->id,
            'message' => $message->message,
            'is_admin' => $message->is_admin,
            'is_read' => $message->is_read,
            'sender_name' => $message->is_admin ? 'Support OUAGA CHAP' : $message->user?->name,
            'created_at' => $message->created_at->toIso8601String(),
        ];
    }

    private function getStatusLabel(string $status): string
    {
        return match ($status) {
            'open' => 'Ouvert',
            'in_progress' => 'En cours',
            'resolved' => 'Résolu',
            'closed' => 'Fermé',
            default => $status,
        };
    }

    private function getPriorityLabel(string $priority): string
    {
        return match ($priority) {
            'low' => 'Basse',
            'medium' => 'Moyenne',
            'high' => 'Haute',
            'urgent' => 'Urgente',
            default => $priority,
        };
    }

    private function complaintTypes(): array
    {
        return [
            ['value' => 'delivery_issue', 'label' => '🚚 Problème de livraison', 'icon' => 'truck'],
            ['value' => 'payment_issue', 'label' => '💰 Problème de paiement', 'icon' => 'credit-card'],
            ['value' => 'courier_behavior', 'label' => '👤 Comportement coursier', 'icon' => 'user'],
            ['value' => 'app_bug', 'label' => '🐛 Bug application', 'icon' => 'bug'],
            ['value' => 'other', 'label' => '📋 Autre', 'icon' => 'help-circle'],
        ];
    }
}
