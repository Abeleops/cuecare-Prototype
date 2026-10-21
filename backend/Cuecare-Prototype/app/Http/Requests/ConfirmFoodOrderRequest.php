<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Validates payment details when confirming a food order. */
class ConfirmFoodOrderRequest extends FormRequest
{
    /** Allows validation; order confirmation access is enforced separately. */
    public function authorize(): bool
    {
        return true;
    }

    /** Returns the payment and proof-of-payment validation rules. */
    public function rules(): array
    {
        return [
            'payment_method' => 'required|in:cash,card,e-wallet',
            'amount_received' => [
                'required_if:payment_method,cash',
                'nullable',
                'numeric',
                'min:0',
            ],
            'proof_of_payment' => [
                'required_if:payment_method,e-wallet',
                'file',
                'image',
                'mimes:jpg,jpeg,png',
                'max:5120',
            ],
        ];
    }
}
