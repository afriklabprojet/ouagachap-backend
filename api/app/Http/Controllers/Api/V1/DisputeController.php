<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\OrderStatus;
use App\Models\Complaint;
use App\Models\Order;
use App\Services\CdnService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\File;

class DisputeController extends BaseController
{
    private const PHOTO_MAX_MB           = 5;
    private const COMPLAINT_TYPE         = 'delivery_issue';
    private const COMPLAINT_PRIORITY     = 'high';
    private const COMPLAINT_STATUS       = 'open';
    private const SUBJECT_PREFIX         = 'Litige automatique — livraison #';
    private const PHOTO_EVIDENCE_PREFIX  = '[PREUVE PHOTO: %s]';

    public function __construct(
        private readonly CdnService $cdnService,
    ) {}

    /**
     * POST /api/v1/orders/{order}/dispute
     *
     * Opens an automatic dispute for a delivered order.
     * Attaches the existing delivery photo and/or an uploaded photo as evidence.
     */
    public function store(Request $request, string $orderId): JsonResponse
    {
        $order = Order::where('id', $orderId)
            ->where('client_id', $request->user()->id)
            ->first();

        if ($order === null) {
            return $this->notFound('Commande non trouvée.');
        }

        if ($order->status !== OrderStatus::DELIVERED) {
            return $this->error(
                'Seules les commandes livrées peuvent faire l\'objet d\'un litige.',
                422
            );
        }

        $existingDispute = Complaint::where('order_id', $order->id)
            ->whereIn('status', ['open', 'in_progress'])
            ->first();

        if ($existingDispute !== null) {
            return $this->error('Un litige est déjà ouvert pour cette commande.', 409);
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
            'photo'  => ['nullable', File::image()->max(self::PHOTO_MAX_MB * 1024)],
        ]);

        $description = $this->buildDescription(
            $validated['reason'],
            $order->delivery_photo_url ?? null,
            $request->hasFile('photo') ? $request->file('photo') : null,
            $orderId,
        );

        $complaint = Complaint::create([
            'user_id'     => $request->user()->id,
            'order_id'    => $order->id,
            'courier_id'  => $order->courier_id,
            'type'        => self::COMPLAINT_TYPE,
            'priority'    => self::COMPLAINT_PRIORITY,
            'status'      => self::COMPLAINT_STATUS,
            'subject'     => self::SUBJECT_PREFIX . $order->order_number,
            'description' => $description,
        ]);

        return $this->success(
            $this->formatComplaint($complaint),
            'Litige créé avec succès.',
            201
        );
    }

    private function buildDescription(
        string $reason,
        ?string $deliveryPhotoUrl,
        ?\Illuminate\Http\UploadedFile $uploadedPhoto,
        string $orderId,
    ): string {
        $prefixes = [];

        if ($deliveryPhotoUrl !== null && $deliveryPhotoUrl !== '') {
            $prefixes[] = sprintf(self::PHOTO_EVIDENCE_PREFIX, $deliveryPhotoUrl);
        }

        if ($uploadedPhoto !== null) {
            $uploadedUrl = $this->cdnService->upload(
                $uploadedPhoto,
                'disputes/' . $orderId,
            );
            $prefixes[] = sprintf(self::PHOTO_EVIDENCE_PREFIX, $uploadedUrl);
        }

        if (empty($prefixes)) {
            return $reason;
        }

        return implode("\n", $prefixes) . "\n\n" . $reason;
    }

    private function formatComplaint(Complaint $complaint): array
    {
        return [
            'id'            => $complaint->id,
            'ticket_number' => $complaint->ticket_number,
            'type'          => $complaint->type,
            'priority'      => $complaint->priority,
            'status'        => $complaint->status,
            'subject'       => $complaint->subject,
            'description'   => $complaint->description,
            'order_id'      => $complaint->order_id,
            'courier_id'    => $complaint->courier_id,
            'created_at'    => $complaint->created_at->toIso8601String(),
        ];
    }
}
