<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Validates a food order request's customer or table and menu quantities. */
class StoreOrderRequest extends FormRequest
{
    /** Allows validation; order request access is enforced separately. */
    public function authorize(): bool
    {
        return true;
    }

    /** Returns the customer or table and order line validation rules. */
    public function rules(): array
    {
        return [
            'table_or_customer' => [
                'required',
                'string',
                'max:120',
            ],
            'items' => [
                'required',
                'array',
                'min:1',
            ],
            'items.*.menu_item_id' => [
                'required',
                'exists:menu_items,id',
            ],
            'items.*.quantity' => [
                'required',
                'integer',
                'min:0',
            ],
        ];
    }
}
