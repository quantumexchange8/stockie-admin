<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
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
            'product_name' => 'required|string|max:255',
            'price' => 'required|string|max:255',
            'point' => 'required|string|max:255',
            'category_id' => 'required|integer',
            'keep' => 'required|string|max:255',
        ];
    }

    public function attributes(): array
    {
        return [
            'product_name' => 'Product Name',
            'price' => 'Price',
            'point' => 'Point',
            'category_id' => 'Category',
            'keep' => 'Keep',
        ];
    }

    public function messages(): array
    {
        return [
            'product_name.required' => 'This field is required.',
            'product_name.string' => 'This field must be a string.',
            'product_name.max' => 'This field must not exceed 255 characters.',
            'price.required' => 'This field is required.',
            'price.string' => 'This field must be a string.',
            'price.max' => 'This field must not exceed 255 characters.',
            'point.required' => 'This field is required.',
            'point.string' => 'This field must be a string.',
            'point.max' => 'This field must not exceed 255 characters.',
            'category_id.required' => 'This field is required.',
            'category_id.integer' => 'This field must be an integer.',
            'keep.required' => 'This field is required.',
            'keep.string' => 'This field must be a string.',
            'keep.max' => 'This field must not exceed 255 characters.',
        ];
    }
}
