<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:assigned,picked_up,delivered,cancelled'],
            'latitude' => ['sometimes', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'numeric', 'between:-180,180'],
            'note' => ['sometimes', 'string', 'max:500'],
            'cancellation_reason' => ['required_if:status,cancelled', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Le statut est obligatoire.',
            'status.in' => 'Le statut doit être assigned, picked_up, delivered ou cancelled.',
            'cancellation_reason.required_if' => 'La raison d\'annulation est obligatoire.',
        ];
    }
}
