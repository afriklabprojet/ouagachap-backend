<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreAdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Middleware EnsureIsAdmin handles authorization
    }

    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'unique:users,email'],
            'phone'    => ['required', 'string', 'regex:/^(\+226|00226)?[0-9]{8}$/', 'unique:users,phone'],
            'password' => ['required', Password::min(8)->mixedCase()->numbers()->symbols()],
        ];
    }
}
