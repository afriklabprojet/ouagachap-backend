<?php

namespace App\Http\Requests\Address;

use Illuminate\Foundation\Http\FormRequest;

class StoreSavedAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:50'],
            'address' => ['required', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
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
            'label.required' => 'Le nom de l\'adresse est obligatoire.',
            'label.max' => 'Le nom ne doit pas dépasser 50 caractères.',
            'address.required' => 'L\'adresse est obligatoire.',
            'latitude.required' => 'La latitude est obligatoire.',
            'latitude.between' => 'La latitude doit être entre -90 et 90.',
            'longitude.required' => 'La longitude est obligatoire.',
            'longitude.between' => 'La longitude doit être entre -180 et 180.',
            'type.in' => 'Le type doit être : home, work ou other.',
        ];
    }
}
