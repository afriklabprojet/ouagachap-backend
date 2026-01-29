<?php

namespace App\Http\Requests\Courier;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'is_available' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'is_available.required' => 'La disponibilité est obligatoire.',
            'is_available.boolean' => 'La disponibilité doit être un booléen.',
        ];
    }
}
