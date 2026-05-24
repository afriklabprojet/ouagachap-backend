<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class RateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'review' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'tags'   => ['sometimes', 'nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'rating.required' => 'La note est obligatoire.',
            'rating.min'      => 'La note minimale est 1.',
            'rating.max'      => 'La note maximale est 5.',
        ];
    }
}
