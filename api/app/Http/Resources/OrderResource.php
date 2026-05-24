<?php

namespace App\Http\Resources;

use App\Enums\OrderStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property int    $id
 * @property string $order_number
 * @property \App\Enums\OrderStatus|string $status
 * @property string|null $pickup_address
 * @property float|null  $pickup_latitude
 * @property float|null  $pickup_longitude
 * @property string|null $pickup_contact_name
 * @property string|null $pickup_contact_phone
 * @property string|null $pickup_instructions
 * @property string|null $dropoff_address
 * @property float|null  $dropoff_latitude
 * @property float|null  $dropoff_longitude
 * @property string|null $dropoff_contact_name
 * @property string|null $dropoff_contact_phone
 * @property string|null $dropoff_instructions
 * @property string|null $package_description
 * @property string|null $package_size
 * @property float $total_price
 * @property float $base_price
 * @property float $distance_price
 * @property float $distance_km
 * @property float|null $commission_amount
 * @property float|null $courier_earnings
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status = $this->status instanceof OrderStatus ? $this->status : OrderStatus::tryFrom($this->status);

        $data = [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'status' => $status?->value ?? $this->status,
            'status_label' => $status?->label() ?? '',
            'status_color' => $status?->color() ?? '',

            'client' => $this->whenLoaded('client'),
            'courier' => $this->whenLoaded('courier'),

            'pickup' => [
                'address' => $this->pickup_address,
                'latitude' => $this->pickup_latitude,
                'longitude' => $this->pickup_longitude,
                'contact_name' => $this->pickup_contact_name,
                'contact_phone' => $this->pickup_contact_phone,
                'instructions' => $this->pickup_instructions,
            ],

            'dropoff' => [
                'address' => $this->dropoff_address,
                'latitude' => $this->dropoff_latitude,
                'longitude' => $this->dropoff_longitude,
                'contact_name' => $this->dropoff_contact_name,
                'contact_phone' => $this->dropoff_contact_phone,
                'instructions' => $this->dropoff_instructions,
            ],

            'package' => [
                'description' => $this->package_description,
                'size' => $this->package_size,
            ],

            'pricing' => [
                'total_price' => (float) $this->total_price,
                'base_price' => (float) $this->base_price,
                'distance_price' => (float) $this->distance_price,
                'distance_km' => (float) $this->distance_km,
                'commission_amount' => (float) ($this->commission_amount ?? 0),
                'courier_earnings' => (float) ($this->courier_earnings ?? 0),
                'currency' => 'XOF',
            ],

            'timestamps' => [
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
                'assigned_at' => $this->assigned_at,
                'picked_up_at' => $this->picked_up_at,
                'delivered_at' => $this->delivered_at,
                'cancelled_at' => $this->cancelled_at,
            ],

            'ratings' => [
                'client_rating' => $this->whenLoaded('clientRating'),
                'courier_rating' => $this->whenLoaded('courierRating'),
            ],
        ];

        // Include confirmation code only for the assigned courier
        $user = $request->user();
        if ($user && $this->courier_id && $user->id === $this->courier_id) {
            $data['recipient_confirmation_code'] = $this->recipient_confirmation_code;
        }

        return $data;
    }
}
