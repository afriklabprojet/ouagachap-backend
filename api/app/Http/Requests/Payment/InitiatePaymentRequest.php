<?php

namespace App\Http\Requests\Payment;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InitiatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id'     => ['required', 'string', 'uuid'],
            'method'       => [
                'required',
                'string',
                Rule::in(array_column(PaymentMethod::cases(), 'value')),
            ],
            'phone_number' => ['required', 'string', 'regex:/^(\+226|00226)?[0-9]{8}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'order_id.required'     => 'L\'identifiant de commande est obligatoire.',
            'order_id.uuid'         => 'L\'identifiant de commande est invalide.',
            'method.required'       => 'La méthode de paiement est obligatoire.',
            'method.in'             => 'Méthode de paiement invalide.',
            'phone_number.required' => 'Le numéro de téléphone est obligatoire.',
            'phone_number.regex'    => 'Format de numéro invalide.',
        ];
    }
}
