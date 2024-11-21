<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PointRequest extends FormRequest
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
            'point' => 'required|integer',
            'image' => 'required'
        ];

        $rules['name'] = $this->input('id') 
                        ?   [
                                'required',
                                'string',
                                'max:255',
                                Rule::unique('rankings')->ignore($this->input('id')),
                            ]
                        : 'required|string|max:255|unique:rankings';

        return $rules;
    }

    public function attributes(): array
    {
        return [
            'name' => 'Item Name',
            'point' => 'Point',
            'image' => 'Image',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'This field is required.',
            'name.string' => 'This field must be a string.',
            'name.max' => 'This field must not exceed 255 characters.',
            'name.unique' => 'This field must have a unique code.',
            'point.required' => 'This field is required.',
            'point.integer' => 'This field must be an integer.',
            'image.required' => 'Image is required.',
        ];
    }
}
