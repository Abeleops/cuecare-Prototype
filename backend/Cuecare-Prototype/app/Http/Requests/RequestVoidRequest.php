<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Authorizes a transaction void request and validates its reason. */
class RequestVoidRequest extends FormRequest
{
    /** Returns whether the current account may request a void for the route transaction. */
    public function authorize(): bool
    {
        return $this->user()->can('requestVoid', $this->route('transaction'));
    }

    /** Returns the permitted reason codes and conditional note validation rules. */
    public function rules(): array
    {
        return [
            'reason_code' => [
                'required',
                Rule::in([
                    'spoilage',
                    'damage',
                    'customer complaint',
                    'data entry error',
                    'theft or loss',
                    'other',
                ]),
            ],
            'reason' => 'required_if:reason_code,other|nullable|string|max:1000',
        ];
    }
}
