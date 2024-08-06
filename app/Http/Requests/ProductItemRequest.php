<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductItemRequest extends FormRequest
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
            // 'product_id' => 'required|integer',
            'inventory_item_id' => 'required|integer',
            'qty' => 'required|integer',
        ];
    }

    public function attributes(): array
    {
        return [
            'inventory_item_id' => 'Inventory Item',
            'qty' => 'Stock Quantity',
        ];
    }

    public function messages(): array
    {
        return [
            'inventory_item_id.required' => 'This field is required.',
            'inventory_item_id.integer' => 'This field must be an integer.',
            'qty.required' => 'This field is required.',
            'qty.string' => 'This field must be a string.',
            'qty.max' => 'This field must not exceed 255 characters.',
        ];
    }
}
