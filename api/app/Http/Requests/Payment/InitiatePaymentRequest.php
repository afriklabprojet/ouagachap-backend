<?php

namespace App\Http\Requests\Payment;

use App\Enums\PaymentMethod;
use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InitiatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Only clients can initiate payments
        return $this->user()?->role === UserRole::CLIENT;
    }

    public function rules(): array
    {
        return [
            'order_id' => [
                'required',
                'uuid',
                'exists:orders,id',
            ],
            'method' => [
                'required',
                'string',
                Rule::in(array_column(PaymentMethod::cases(), 'value')),
            ],
            'phone_number' => [
                'required',
                'string',
                'regex:/^(\+226|00226)?[0-9]{8}$/',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'order_id.required' => 'L\'ID de commande est obligatoire.',
            'order_id.uuid' => 'Format d\'ID de commande invalide.',
            'order_id.exists' => 'Commande non trouvée.',
            'method.required' => 'La méthode de paiement est obligatoire.',
            'method.in' => 'Méthode de paiement invalide.',
            'phone_number.required' => 'Le numéro de téléphone est obligatoire.',
            'phone_number.regex' => 'Format de téléphone invalide (ex: +22670123456).',
        ];
    }

    /**
     * Prepare data for validation
     */
    protected function prepareForValidation(): void
    {
        // Normalize phone number
        if ($this->phone_number) {
            $phone = preg_replace('/\s+/', '', $this->phone_number);
            $this->merge(['phone_number' => $phone]);
        }
    }
}
