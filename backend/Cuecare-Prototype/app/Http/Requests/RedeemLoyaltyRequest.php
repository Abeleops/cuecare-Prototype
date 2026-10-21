<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Validates customer loyalty redemption details. */
class RedeemLoyaltyRequest extends FormRequest
{
    /** Allows validation; loyalty redemption access is enforced separately. */
    public function authorize(): bool
    {
        return true;
    }

    /** Returns the customer, points, and notes validation rules. */
    public function rules(): array
    {
        return [
            'customer_id' => [
                'required',
                'exists:customers,id',
            ],
            'points' => [
                'required',
                'integer',
                'min:1',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }
}
