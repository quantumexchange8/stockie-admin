<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventoryRequest extends FormRequest
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
            'category_id' => 'required|integer',
            // 'image' => 'required|image',
        ];

        if ($this->input('id')) {
            $rules['name'] = [
                'required',
                'string',
                'max:255',
                Rule::unique('iventories')->ignore($this->input('id')),
            ];
        } else {
            $rules['name'] = 'required|string|max:255|unique:iventories';
        }

        return $rules;
    }

    public function attributes(): array
    {
        return [
            'name' => 'Group Name',
            'category_id' => 'Category Id',
            'image' => 'Image',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'This field is required.',
            'name.string' => 'This field must be a string.',
            'name.max' => 'This field must not exceed 255 characters.',
            'name.unique' => 'This field must have a unique name.',
            'category_id.required' => 'This field is required.',
            'category_id.integer' => 'This field must be an integer.',
            'image.required' => 'This field is required.',
            'image.string' => 'This field must be an image.',
        ];
    }
}
