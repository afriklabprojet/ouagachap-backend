<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property int    $id
 * @property string $name
 * @property string $code
 * @property string|null $description
 * @property bool   $is_active
 * @property float  $base_price
 * @property float  $price_per_km
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class ZoneResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'pricing' => [
                'base_price' => (float) $this->base_price,
                'price_per_km' => (float) $this->price_per_km,
                'currency' => 'XOF',
            ],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
