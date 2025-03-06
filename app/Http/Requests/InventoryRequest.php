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
            // 'category_id' => 'required|integer',
            'image' => 'required|max:8000',
        ];

        if ($this->input('id')) {
            $rules['name'] = [
                'required',
                'string',
                'max:255',
                Rule::unique('iventories')->ignore($this->input('id'))->whereNull('deleted_at'),
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
            // 'category_id' => 'Category Id',
            'image' => 'Image',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'This field is required.',
            'string' => 'This field must be a string.',
            'name.max' => 'This field must not exceed 255 characters.',
            'unique' => 'This field must have a unique name.',
            'integer' => 'This field must be an integer.',
            'image.required' => 'Image is required.',
            'image.max' => 'The size of the image is too big.'
        ];
    }
}
