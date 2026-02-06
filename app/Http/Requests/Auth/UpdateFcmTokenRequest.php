<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFcmTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fcm_token' => ['required', 'string', 'max:500'],
            'device_type' => ['nullable', 'string', 'in:ios,android'],
        ];
    }

    public function messages(): array
    {
        return [
            'fcm_token.required' => 'Le token FCM est requis.',
            'device_type.in' => 'Le type d\'appareil doit être ios ou android.',
        ];
    }
}
