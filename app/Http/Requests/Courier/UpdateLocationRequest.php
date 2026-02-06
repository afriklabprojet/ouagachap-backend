<?php

namespace App\Http\Requests\Courier;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ];
    }

    public function messages(): array
    {
        return [
            'latitude.required' => 'La latitude est obligatoire.',
            'latitude.between' => 'La latitude doit être entre -90 et 90.',
            'longitude.required' => 'La longitude est obligatoire.',
            'longitude.between' => 'La longitude doit être entre -180 et 180.',
        ];
    }
}
