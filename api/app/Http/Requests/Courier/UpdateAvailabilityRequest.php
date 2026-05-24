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
            'is_available.required' => 'Le statut de disponibilité est obligatoire.',
            'is_available.boolean'  => 'Le statut de disponibilité doit être vrai ou faux.',
        ];
    }
}
