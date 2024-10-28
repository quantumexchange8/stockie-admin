<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DiscountRequest extends FormRequest
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
            //
            'discount_name' => 'required|string|max:255',
            'discount_rate' => [
                'required',
                'numeric',
                Rule::when($this->discount_type === 'percentage', 'between:0,100')
            ],
            'discount_product' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'discount_name.required' => 'This field is required.',
            'discount_name.string' => 'This field must be a string.',
            
            'discount_rate.required' => 'This field is required.',
            'discount_rate.numeric' => 'This field must be a numeric value.',
            'discount_rate.between' => 'Only 0 to 100 is allowed.',

            'discount_product.required' => 'This field is required.'
        ];
    }
}
