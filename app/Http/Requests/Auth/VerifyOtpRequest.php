<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'regex:/^(\+226|00226)?[0-9]{8}$/'],
            'code' => ['required', 'string', 'size:6'],
            'device_name' => ['sometimes', 'string', 'max:255'],
            'app_type' => ['sometimes', 'string', 'in:client,courier'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => 'Le numéro de téléphone est obligatoire.',
            'phone.regex' => 'Le format du numéro de téléphone est invalide.',
            'code.required' => 'Le code OTP est obligatoire.',
            'code.size' => 'Le code OTP doit contenir 6 chiffres.',
            'app_type.in' => 'Le type d\'application doit être "client" ou "courier".',
        ];
    }
}
