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
            'qty' => 'required|integer|min:1',
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
            'required' => 'This field is required.',
            'string' => 'This field must be a string.',
            'max' => 'This field must not exceed 255 characters.',
            'integer' => 'This field must be an integer.',
            'min' => 'A minimum of 1 stock available is required.',
        ];
    }
}
