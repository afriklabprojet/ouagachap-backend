<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource pour transformer les utilisateurs
 */
class UserResource extends JsonResource
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
            'phone' => $this->phone,
            'name' => $this->name,
            'email' => $this->email,
            'avatar_url' => $this->avatar_url,
            'role' => $this->role->value,
            'status' => $this->status->value,
            'is_active' => $this->isActive(),
            
            // Informations spécifiques coursier
            $this->mergeWhen($this->isCourier(), [
                'vehicle_type' => $this->vehicle_type,
                'vehicle_plate' => $this->vehicle_plate,
                'vehicle_model' => $this->vehicle_model,
                'is_available' => $this->is_available,
                'current_location' => $this->when(
                    $this->current_latitude && $this->current_longitude,
                    [
                        'latitude' => (float) $this->current_latitude,
                        'longitude' => (float) $this->current_longitude,
                        'updated_at' => $this->location_updated_at?->toIso8601String(),
                    ]
                ),
            ]),
            
            // Stats
            'stats' => [
                'total_orders' => $this->total_orders,
                'average_rating' => (float) $this->average_rating,
                'total_ratings' => $this->total_ratings,
            ],
            
            // Wallet (visible uniquement pour le propriétaire)
            $this->mergeWhen($this->shouldShowWallet($request), [
                'wallet_balance' => (float) $this->wallet_balance,
            ]),
            
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }

    /**
     * Détermine si le wallet doit être affiché
     */
    private function shouldShowWallet(Request $request): bool
    {
        $user = $request->user();
        return $user && ($user->id === $this->id || $user->isAdmin());
    }
}
