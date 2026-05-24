<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WithdrawalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Auth handled by middleware
    }

    public function rules(): array
    {
        return [
            'amount'         => 'required|numeric|min:500',
            'payment_method' => 'required|in:mobile_money,bank_transfer',
            'phone'          => 'required_if:payment_method,mobile_money|string',
            'provider'       => 'required_if:payment_method,mobile_money|in:orange_money,moov_money,wave,mtn_money,djamo',
            'bank_name'      => 'required_if:payment_method,bank_transfer|string',
            'bank_account'   => 'required_if:payment_method,bank_transfer|string',
        ];
    }
}
