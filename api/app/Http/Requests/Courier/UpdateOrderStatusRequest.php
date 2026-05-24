<?php

namespace App\Http\Requests\Courier;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === UserRole::COURIER;
    }

    public function rules(): array
    {
        return [
            'status' => [
                'required',
                'string',
                Rule::in(['accepted', 'picking_up', 'picked_up', 'in_transit', 'delivered']),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Le statut est obligatoire.',
            'status.in' => 'Le statut fourni n\'est pas valide.',
        ];
    }
}
