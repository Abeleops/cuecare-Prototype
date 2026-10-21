<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Validates the details used to create an autocare staff account. */
class StoreAutocareStaffRequest extends FormRequest
{
    /** Returns whether an account is authenticated for this request. */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /** Returns the staff name, unique email, and password validation rules. */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
        ];
    }
}
