<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Validates assignment of exactly three distinct, unlocked autocare staff accounts. */
class AssignAutocareStaffRequest extends FormRequest
{
    /** Returns whether an account is authenticated for this request. */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /** Returns the staff assignment validation rules. */
    public function rules(): array
    {
        return [
            'assigned_staff' => 'required|array|size:3',
            'assigned_staff.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('users', 'id')
                    ->where(fn ($q) => $q->where('role', 'autocare_staff')->whereNull('locked_at')),
            ],
        ];
    }
}
