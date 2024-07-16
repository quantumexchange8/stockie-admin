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
            'inventory_id' => 'required|integer',
            'item_name' => 'required|string|max:255',
            'item_cat_id' => 'required|integer',
            'stock_qty' => 'required|decimal:0,2',
            'status' => 'required|string|max:255',
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
        
        // dd($rules);
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
            'status' => 'Status',
        ];
    }

    public function messages(): array
    {
        return [
            'inventory_id.required' => 'This field is required.',
            'inventory_id.integer' => 'This field must be an integer.',
            'item_name.required' => 'This field is required.',
            'item_name.string' => 'This field must be a string.',
            'item_name.max' => 'This field must not exceed 255 characters.',
            'item_code.required' => 'This field is required.',
            'item_code.string' => 'This field must be a string.',
            'item_code.max' => 'This field must not exceed 255 characters.',
            'item_code.unique' => 'This field must have a unique code.',
            'item_cat_id.required' => 'This field is required.',
            'item_cat_id.integer' => 'This field must be an integer.',
            'stock_qty.required' => 'This field is required.',
            'stock_qty.decimal' => 'This field must have a decimal point of 2.',
            'status.required' => 'This field is required.',
            'status.string' => 'This field must be a string.',
            'status.max' => 'This field must not exceed 255 characters.',
        ];
    }
}
