<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PointItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'inventory_item_id' => 'required|integer',
            'item_qty' => 'required|integer|min:1',
        ];
    }

    public function attributes(): array
    {
        return [
            'inventory_item_id' => 'Inventory Item',
            'item_qty' => 'Item Quantity',
        ];
    }

    public function messages(): array
    {
        return [
            'inventory_item_id.required' => 'This field is required.',
            'inventory_item_id.integer' => 'This field must be an integer.',
            'item_qty.required' => 'This field is required.',
            'item_qty.integer' => 'This field must be an integer.',
            'item_qty.min' => 'A minimum of 1 stock available is required.',
        ];
    }
}
