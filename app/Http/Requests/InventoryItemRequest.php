<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventoryItemRequest extends FormRequest
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
        $rules = [
            'item_name' => 'required|string|max:255',
            'item_cat_id' => 'required|integer',
            'stock_qty' => 'required|decimal:0,2|min:0',
            'low_stock_qty' => 'required|integer|min:0',
            'status' => 'required|string|max:255',
            'keep' => 'required|string|max:255',
        ];

        // foreach ($this->input('items') as $index => $item) {
        //     if (isset($item['id'])) {
        //         $rules["items.$index.item_code"] = [
        //             'required',
        //             'string',
        //             'max:255',
        //             Rule::unique('iventory_items')->ignore($item['id'], 'id'),
        //         ];
        //     } else {
        //         $rules["items.$index.item_code"] = 'required|string|max:255|unique:iventory_items';
        //     }
        // }
        return $rules;
    }

    public function attributes(): array
    {
        return [
            'inventory_id' => 'Inventory ID',
            'item_name' => 'Item Name',
            'item_code' => 'Item Code',
            'item_cat_id' => 'Item Category ID',
            'stock_qty' => 'Stock Quantity',
            'low_stock_qty' => 'Low Stock At',
            'keep' => 'Keep',
            'status' => 'Status',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'This field is required.',
            'string' => 'This field must be a string.',
            'max' => 'This field must not exceed 255 characters.',
            'integer' => 'This field must be an integer.',
            'array' => 'This field must be an array.',
            'unique' => 'This unique code has been taken.',
            'decimal' => 'This field must have a decimal point of 2.',
        ];
    }
}
