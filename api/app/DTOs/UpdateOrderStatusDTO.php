<?php

namespace App\DTOs;

use App\Enums\OrderStatus;
use Illuminate\Http\Request;

readonly class UpdateOrderStatusDTO
{
    public function __construct(
        public OrderStatus $status,
        public ?string     $note = null,
        public ?float      $latitude = null,
        public ?float      $longitude = null,
        public ?string     $cancellationReason = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            status:             OrderStatus::from($request->input('status')),
            note:               $request->input('note'),
            latitude:           $request->input('latitude') !== null
                                    ? (float) $request->input('latitude')
                                    : null,
            longitude:          $request->input('longitude') !== null
                                    ? (float) $request->input('longitude')
                                    : null,
            cancellationReason: $request->input('cancellation_reason'),
        );
    }

    public static function fromArray(array $data): self
    {
        return new self(
            status:             OrderStatus::from($data['status']),
            note:               $data['note'] ?? null,
            latitude:           isset($data['latitude']) ? (float) $data['latitude'] : null,
            longitude:          isset($data['longitude']) ? (float) $data['longitude'] : null,
            cancellationReason: $data['cancellation_reason'] ?? null,
        );
    }

    /** Résout la note à passer à updateStatus() (note ou cancellation_reason). */
    public function resolvedNote(): ?string
    {
        return $this->note ?? $this->cancellationReason;
    }
}
