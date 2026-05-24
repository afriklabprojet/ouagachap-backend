<?php

namespace App\Http\Requests\Address;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSavedAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'label' => ['sometimes', 'required', 'string', 'max:50'],
            'address' => ['sometimes', 'required', 'string', 'max:255'],
            'latitude' => ['sometimes', 'required', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'required', 'numeric', 'between:-180,180'],
            'contact_name' => ['nullable', 'string', 'max:100'],
            'contact_phone' => ['nullable', 'string', 'max:20'],
            'instructions' => ['nullable', 'string', 'max:500'],
            'is_default' => ['boolean'],
            'type' => ['in:home,work,other'],
        ];
    }

    public function messages(): array
    {
        return [
            'label.max' => 'Le nom ne doit pas dépasser 50 caractères.',
            'latitude.between' => 'La latitude doit être entre -90 et 90.',
            'longitude.between' => 'La longitude doit être entre -180 et 180.',
            'type.in' => 'Le type doit être : home, work ou other.',
        ];
    }
}
