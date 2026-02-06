<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource pour transformer les commandes
 */
class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'status_color' => $this->status->color(),
            
            // Pickup
            'pickup' => [
                'address' => $this->pickup_address,
                'latitude' => (float) $this->pickup_latitude,
                'longitude' => (float) $this->pickup_longitude,
                'contact_name' => $this->pickup_contact_name,
                'contact_phone' => $this->pickup_contact_phone,
                'instructions' => $this->pickup_instructions,
            ],
            
            // Dropoff
            'dropoff' => [
                'address' => $this->dropoff_address,
                'latitude' => (float) $this->dropoff_latitude,
                'longitude' => (float) $this->dropoff_longitude,
                'contact_name' => $this->dropoff_contact_name,
                'contact_phone' => $this->dropoff_contact_phone,
                'instructions' => $this->dropoff_instructions,
            ],
            
            // Package
            'package' => [
                'description' => $this->package_description,
                'size' => $this->package_size,
            ],
            
            // Pricing
            'pricing' => [
                'distance_km' => (float) $this->distance_km,
                'base_price' => (float) $this->base_price,
                'distance_price' => (float) $this->distance_price,
                'total_price' => (float) $this->total_price,
                'currency' => 'XOF',
            ],
            
            // Relations
            'client' => new UserResource($this->whenLoaded('client')),
            'courier' => new UserResource($this->whenLoaded('courier')),
            'zone' => new ZoneResource($this->whenLoaded('zone')),
            
            // Timestamps
            'timestamps' => [
                'created_at' => $this->created_at->toIso8601String(),
                'assigned_at' => $this->assigned_at?->toIso8601String(),
                'picked_up_at' => $this->picked_up_at?->toIso8601String(),
                'delivered_at' => $this->delivered_at?->toIso8601String(),
                'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            ],
            
            // Cancellation
            $this->mergeWhen($this->cancelled_at, [
                'cancellation_reason' => $this->cancellation_reason,
            ]),
            
            // Ratings
            'ratings' => [
                'client_rating' => $this->client_rating,
                'client_review' => $this->client_review,
                'courier_rating' => $this->courier_rating,
                'courier_review' => $this->courier_review,
            ],
            
            // Code de confirmation (uniquement pour le destinataire ou le coursier)
            $this->mergeWhen($this->shouldShowConfirmationCode($request), [
                'recipient_confirmation_code' => $this->recipient_confirmation_code,
                'recipient_confirmed' => $this->recipient_confirmed,
            ]),
        ];
    }

    /**
     * Détermine si le code de confirmation doit être affiché
     */
    private function shouldShowConfirmationCode(Request $request): bool
    {
        $user = $request->user();
        if (!$user) return false;
        
        return $user->id === $this->courier_id 
            || $user->id === $this->recipient_user_id
            || $user->isAdmin();
    }
}
