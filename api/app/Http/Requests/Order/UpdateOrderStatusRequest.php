<?php

namespace App\Http\Requests\Order;

use App\Enums\OrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => [
                'required',
                'string',
                Rule::in(array_column(OrderStatus::cases(), 'value')),
            ],
            'note'              => ['sometimes', 'nullable', 'string', 'max:500'],
            'latitude'          => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'longitude'         => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],
            'cancellation_reason' => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Le nouveau statut est obligatoire.',
            'status.in'       => 'Statut invalide.',
        ];
    }
}
