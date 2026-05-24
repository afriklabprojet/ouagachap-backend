<?php

namespace App\Http\Requests\Courier;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class UpdateOnlineStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === UserRole::COURIER;
    }

    public function rules(): array
    {
        return [
            'is_online' => ['required', 'boolean'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }

    public function messages(): array
    {
        return [
            'is_online.required' => 'Le statut en ligne est obligatoire.',
            'is_online.boolean' => 'Le statut en ligne doit être un booléen.',
            'latitude.numeric' => 'La latitude doit être un nombre.',
            'latitude.between' => 'La latitude doit être entre -90 et 90.',
            'longitude.numeric' => 'La longitude doit être un nombre.',
            'longitude.between' => 'La longitude doit être entre -180 et 180.',
        ];
    }
}
