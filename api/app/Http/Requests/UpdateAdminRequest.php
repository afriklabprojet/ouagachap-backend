<?php

namespace App\Http\Requests;

use App\Enums\UserStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Middleware EnsureIsAdmin handles authorization
    }

    public function rules(): array
    {
        $admin = $this->route('admin');

        return [
            'name'   => ['sometimes', 'string', 'max:255'],
            'email'  => ['sometimes', 'email', Rule::unique('users')->ignore($admin->id)],
            'phone'  => ['sometimes', 'string', 'regex:/^(\+226|00226)?[0-9]{8}$/', Rule::unique('users')->ignore($admin->id)],
            'status' => ['sometimes', Rule::enum(UserStatus::class)],
        ];
    }
}
