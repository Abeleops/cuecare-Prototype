<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Validates inventory item data and resolves custom unit values. */
class InventoryItemRequest extends FormRequest
{
    /** Allows validation; inventory management access is enforced separately. */
    public function authorize(): bool
    {
        return true;
    }

    /** Returns the inventory fields and conditional custom-unit validation rules. */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:120',
            'quantity' => 'required|numeric|min:0',
            'reorder_level' => 'required|numeric|min:0',
            'unit' => 'required|in:pcs,kg,g,L,mL,bottle,pack,box,sachet,shot,serving,Other',
            'unit_other' => 'required_if:unit,Other|nullable|string|max:20',
        ];
    }

    /** Returns validated item data with a custom unit replacing the Other selection. */
    public function inventoryData(): array
    {
        $data = $this->validated();

        if (($data['unit'] ?? null) === 'Other') {
            $data['unit'] = $data['unit_other'];
        }

        unset($data['unit_other']);

        return $data;
    }
}
