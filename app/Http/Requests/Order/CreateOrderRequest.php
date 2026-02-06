<?php

namespace App\Http\Requests\Order;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Only clients can create orders
        return $this->user()?->role === UserRole::CLIENT;
    }

    public function rules(): array
    {
        return [
            // Pickup - Strict validation
            'pickup_address' => ['required', 'string', 'min:5', 'max:500'],
            'pickup_latitude' => ['required', 'numeric', 'between:-90,90', 'regex:/^-?\d{1,2}\.\d{4,8}$/'],
            'pickup_longitude' => ['required', 'numeric', 'between:-180,180', 'regex:/^-?\d{1,3}\.\d{4,8}$/'],
            'pickup_contact_name' => ['required', 'string', 'min:2', 'max:100'],
            'pickup_contact_phone' => ['required', 'string', 'regex:/^(\+226|00226)?[0-9]{8}$/'],
            'pickup_instructions' => ['sometimes', 'nullable', 'string', 'max:500'],

            // Dropoff - Strict validation
            'dropoff_address' => ['required', 'string', 'min:5', 'max:500'],
            'dropoff_latitude' => ['required', 'numeric', 'between:-90,90', 'regex:/^-?\d{1,2}\.\d{4,8}$/'],
            'dropoff_longitude' => ['required', 'numeric', 'between:-180,180', 'regex:/^-?\d{1,3}\.\d{4,8}$/'],
            'dropoff_contact_name' => ['required', 'string', 'min:2', 'max:100'],
            'dropoff_contact_phone' => ['required', 'string', 'regex:/^(\+226|00226)?[0-9]{8}$/'],
            'dropoff_instructions' => ['sometimes', 'nullable', 'string', 'max:500'],

            // Package - Strict validation
            'package_description' => ['required', 'string', 'min:3', 'max:500'],
            'package_size' => ['sometimes', 'string', Rule::in(['small', 'medium', 'large'])],

            // Zone - Must exist
            'zone_id' => ['sometimes', 'nullable', 'integer', 'exists:zones,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'pickup_address.required' => 'L\'adresse de récupération est obligatoire.',
            'pickup_address.min' => 'L\'adresse de récupération doit contenir au moins 5 caractères.',
            'pickup_latitude.required' => 'La latitude de récupération est obligatoire.',
            'pickup_latitude.regex' => 'Format de latitude invalide.',
            'pickup_longitude.required' => 'La longitude de récupération est obligatoire.',
            'pickup_longitude.regex' => 'Format de longitude invalide.',
            'pickup_contact_name.required' => 'Le nom du contact de récupération est obligatoire.',
            'pickup_contact_phone.required' => 'Le téléphone du contact de récupération est obligatoire.',
            'pickup_contact_phone.regex' => 'Format de téléphone invalide (ex: +22670123456).',
            'dropoff_address.required' => 'L\'adresse de livraison est obligatoire.',
            'dropoff_address.min' => 'L\'adresse de livraison doit contenir au moins 5 caractères.',
            'dropoff_latitude.required' => 'La latitude de livraison est obligatoire.',
            'dropoff_latitude.regex' => 'Format de latitude invalide.',
            'dropoff_longitude.required' => 'La longitude de livraison est obligatoire.',
            'dropoff_longitude.regex' => 'Format de longitude invalide.',
            'dropoff_contact_name.required' => 'Le nom du contact de livraison est obligatoire.',
            'dropoff_contact_phone.required' => 'Le téléphone du contact de livraison est obligatoire.',
            'dropoff_contact_phone.regex' => 'Format de téléphone invalide (ex: +22670123456).',
            'package_description.required' => 'La description du colis est obligatoire.',
            'package_description.min' => 'La description du colis doit contenir au moins 3 caractères.',
            'package_size.in' => 'La taille du colis doit être small, medium ou large.',
            'zone_id.exists' => 'Zone invalide.',
        ];
    }

    /**
     * Prepare data for validation - sanitize inputs
     */
    protected function prepareForValidation(): void
    {
        // Trim string inputs
        $this->merge([
            'pickup_address' => trim($this->pickup_address ?? ''),
            'pickup_contact_name' => trim($this->pickup_contact_name ?? ''),
            'pickup_instructions' => $this->pickup_instructions ? trim($this->pickup_instructions) : null,
            'dropoff_address' => trim($this->dropoff_address ?? ''),
            'dropoff_contact_name' => trim($this->dropoff_contact_name ?? ''),
            'dropoff_instructions' => $this->dropoff_instructions ? trim($this->dropoff_instructions) : null,
            'package_description' => trim($this->package_description ?? ''),
        ]);
    }
}
