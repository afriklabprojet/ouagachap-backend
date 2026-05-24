<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterCourierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone'         => ['required', 'string', 'regex:/^(\+226|00226)?[0-9]{8}$/'],
            'name'          => ['required', 'string', 'max:255'],
            'email'         => ['sometimes', 'nullable', 'email', 'max:255'],
            'password'      => ['required', 'string', 'confirmed', 'min:8'],
            'vehicle_type'  => ['required', 'string', 'in:moto,velo,voiture,camionnette'],
            'vehicle_plate' => ['required', 'string', 'max:20'],
            'vehicle_model' => ['sometimes', 'nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required'          => 'Le numéro de téléphone est obligatoire.',
            'phone.regex'             => 'Format invalide. Utilisez 70123456 ou +22670123456.',
            'name.required'           => 'Le nom complet est obligatoire.',
            'password.required'       => 'Le mot de passe est obligatoire.',
            'password.confirmed'      => 'La confirmation du mot de passe ne correspond pas.',
            'password.min'            => 'Le mot de passe doit contenir au moins 8 caractères.',
            'vehicle_type.required'   => 'Le type de véhicule est obligatoire.',
            'vehicle_type.in'         => 'Type de véhicule invalide (moto, velo, voiture, camionnette).',
            'vehicle_plate.required'  => 'La plaque d\'immatriculation est obligatoire.',
        ];
    }
}
