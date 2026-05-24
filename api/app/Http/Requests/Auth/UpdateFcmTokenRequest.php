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
            'fcm_token'   => ['required', 'string', 'max:512'],
            'device_type' => ['sometimes', 'nullable', 'string', 'in:android,ios,web'],
        ];
    }

    public function messages(): array
    {
        return [
            'fcm_token.required' => 'Le FCM token est obligatoire.',
        ];
    }
}
