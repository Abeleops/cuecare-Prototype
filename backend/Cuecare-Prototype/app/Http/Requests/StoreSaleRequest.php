<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Validates POS sale lines, customer selection, and payment details. */
class StoreSaleRequest extends FormRequest
{
    /** Allows validation; sale creation access is enforced separately. */
    public function authorize(): bool
    {
        return true;
    }

    /** Returns the sale line and conditional payment validation rules. */
    public function rules(): array
    {
        return [
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.menu_item_id' => 'nullable|exists:menu_items,id',
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
            'customer_id' => 'nullable|exists:customers,id',
        ];
    }
}
