<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Authorizes and validates a charged autocare booking with three assigned staff. */
class StoreAutocareBookingRequest extends FormRequest
{
    /** Returns whether the current account has autocare creation access. */
    public function authorize(): bool
    {
        return auth()->user()?->hasPermissionTo('autocare.access') ?? false;
    }

    /** Returns the vehicle, owner, staffing, service, and payment validation rules. */
    public function rules(): array
    {
        return [
            'vehicle_type' => 'required|string|max:50',
            'plate_number' => 'required|string|max:30',
            'owner_contact' => 'required|string|max:30',
            'owner_name' => 'required|string|max:120',
            'model' => 'required|string|max:80',
            'service_catalog_id' => 'required|exists:service_catalogs,id',
            'scheduled_date' => 'required|date',
            'assigned_staff' => 'required|array|size:3',
            'assigned_staff.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('users', 'id')
                    ->where(fn ($q) => $q->where('role', 'autocare_staff')->whereNull('locked_at')),
            ],
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
