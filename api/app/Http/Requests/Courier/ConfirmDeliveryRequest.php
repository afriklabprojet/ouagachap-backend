<?php

namespace App\Http\Requests\Courier;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class ConfirmDeliveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === UserRole::COURIER;
    }

    public function rules(): array
    {
        return [
            'confirmation_code' => ['required', 'string', 'size:6'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'confirmation_code.required' => 'Le code de confirmation est obligatoire.',
            'confirmation_code.size' => 'Le code de confirmation doit contenir exactement 6 caractères.',
            'photo.image' => 'La photo doit être une image valide.',
            'photo.max' => 'La photo ne doit pas dépasser 5 Mo.',
        ];
    }
}
