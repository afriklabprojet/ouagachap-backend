<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class EstimateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pickup_latitude' => ['required', 'numeric', 'between:-90,90'],
            'pickup_longitude' => ['required', 'numeric', 'between:-180,180'],
            'dropoff_latitude' => ['required', 'numeric', 'between:-90,90'],
            'dropoff_longitude' => ['required', 'numeric', 'between:-180,180'],
            'zone_id' => ['sometimes', 'nullable', 'integer', 'exists:zones,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'pickup_latitude.required' => 'La latitude de récupération est obligatoire.',
            'pickup_latitude.between' => 'La latitude doit être entre -90 et 90.',
            'pickup_longitude.required' => 'La longitude de récupération est obligatoire.',
            'pickup_longitude.between' => 'La longitude doit être entre -180 et 180.',
            'dropoff_latitude.required' => 'La latitude de livraison est obligatoire.',
            'dropoff_latitude.between' => 'La latitude doit être entre -90 et 90.',
            'dropoff_longitude.required' => 'La longitude de livraison est obligatoire.',
            'dropoff_longitude.between' => 'La longitude doit être entre -180 et 180.',
            'zone_id.exists' => 'La zone sélectionnée n\'existe pas.',
        ];
    }
}
