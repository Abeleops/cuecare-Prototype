<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Validates audit viewer account, action, and date range filters. */
class AuditFilterRequest extends FormRequest
{
    /** Allows validation; audit access is enforced separately. */
    public function authorize(): bool
    {
        return true;
    }

    /** Returns the audit filter validation rules. */
    public function rules(): array
    {
        return [
            'user_id' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],
            'action' => [
                'nullable',
                'string',
                'max:100',
            ],
            'date_from' => [
                'nullable',
                'date',
            ],
            'date_to' => [
                'nullable',
                'date',
                'after_or_equal:date_from',
            ],
        ];
    }
}
