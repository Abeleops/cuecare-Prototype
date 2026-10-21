<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Validates menu item details and optional picture uploads. */
class MenuItemRequest extends FormRequest
{
    /** Returns whether an account is authenticated for this request. */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /** Returns the menu fields and picture upload validation rules. */
    public function rules(): array
    {
        return [
            'menu_category_id' => 'required|exists:menu_categories,id',
            'name' => 'required|string|max:120',
            'price' => 'required|numeric|min:0',
            'available' => 'nullable|boolean',
            'picture' => [
                'nullable',
                'file',
                'image',
                'mimes:jpg,jpeg,png',
                'max:5120',
            ],
        ];
    }
}
