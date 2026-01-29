<?php

namespace App\Http\Requests\Auth;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class RegisterCourierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'regex:/^(\+226|00226)?[0-9]{8}$/', 'unique:users,phone'],
            'name' => ['required', 'string', 'max:255'],
            'vehicle_type' => ['required', 'string', 'in:moto,velo,voiture'],
            'vehicle_plate' => ['required', 'string', 'max:20'],
            'vehicle_model' => ['sometimes', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => 'Le numéro de téléphone est obligatoire.',
            'phone.regex' => 'Le format du numéro de téléphone est invalide.',
            'phone.unique' => 'Ce numéro de téléphone est déjà utilisé.',
            'name.required' => 'Le nom est obligatoire.',
            'vehicle_type.required' => 'Le type de véhicule est obligatoire.',
            'vehicle_type.in' => 'Le type de véhicule doit être moto, vélo ou voiture.',
            'vehicle_plate.required' => 'La plaque d\'immatriculation est obligatoire.',
        ];
    }
}
