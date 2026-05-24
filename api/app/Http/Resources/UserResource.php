<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property int    $id
 * @property string $name
 * @property string $phone
 * @property string $email
 * @property \App\Enums\UserRole|string|null $role
 * @property string|null $avatar_url
 * @property string|null $vehicle_type
 * @property string|null $vehicle_plate
 * @property string|null $vehicle_model
 * @property float|null  $wallet_balance
 * @property bool|null   $is_available
 * @property string|null $fcm_token
 * @property \Illuminate\Support\Carbon $created_at
 */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'phone'          => $this->phone,
            'email'          => $this->email,
            'role'           => $this->role?->value ?? $this->role,
            'avatar_url'     => $this->avatar_url,
            'vehicle_type'   => $this->vehicle_type,
            'vehicle_plate'  => $this->vehicle_plate,
            'vehicle_model'  => $this->vehicle_model,
            'wallet_balance' => $this->wallet_balance,
            'total_earnings' => $this->when(
                in_array($this->role?->value ?? $this->role, ['courier', 'driver']),
                fn () => (float) ($this->courierOrders()
                    ->where('status', 'delivered')
                    ->sum('courier_earnings') ?? 0),
            ),
            'is_available'   => $this->is_available,
            'fcm_token'      => $this->when($request->user()?->id === $this->id, $this->fcm_token),
            'account_status'          => $this->status?->value ?? $this->status,
            'phone_verified_at'       => $this->phone_verified_at,
            'is_phone_verified'       => $this->phone_verified_at !== null,
            'kyc_status'              => $this->kyc_status?->value ?? $this->kyc_status,
            'kyc_rejection_reason'    => $this->kyc_rejection_reason,
            'documents_submitted_at'  => $this->documents_submitted_at,
            'documents_verified_at'   => $this->documents_verified_at,
            'created_at'              => $this->created_at,
        ];
    }
}
