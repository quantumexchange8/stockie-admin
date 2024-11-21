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
            'bucket' => 'required|boolean',
            'product_name' => 'required|string|max:255',
            'price' => 'required|string|max:255',
            'point' => 'required|string|max:255',
            'category_id' => 'required|integer',
            'is_redeemable' => 'required|boolean',
            'image' => 'required'
        ];
    }

    public function attributes(): array
    {
        return [
            'bucket' => 'Bucket',
            'product_name' => 'Product Name',
            'price' => 'Price',
            'point' => 'Point',
            'category_id' => 'Category',
            'is_redeemable' => 'Redeemable',
            'image' => 'Image',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'This field is required.',
            'string' => 'This field must be a string.',
            'max' => 'This field must not exceed 255 characters.',
            'integer' => 'This field must be an integer.',
            'image' => 'Image is required.'
        ];
    }
}
