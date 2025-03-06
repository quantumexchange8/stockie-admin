<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RankingRequest extends FormRequest
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
            'min_amount' => 'required|integer',
            'reward' => 'required|string|max:255',
            'icon' => 'required|max:8000',
        ];

        $rules['name'] = $this->input('id') 
                        ?   [
                                'required',
                                'string',
                                'max:255',
                                Rule::unique('rankings')->ignore($this->input('id'))->whereNull('deleted_at'),
                            ]
                        : 'required|string|max:255|unique:rankings,name,NULL,id,deleted_at,NULL';

        return $rules;
    }

    public function attributes(): array
    {
        return [
            'name' => 'Ranking Name',
            'min_amount' => 'Minimum Amount',
            'reward' => 'Reward',
            'icon' => 'Icon'
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'This field is required.',
            'string' => 'This field must be a string.',
            'reward.max' => 'This field must not exceed 255 characters.',
            'name.max' => 'This field must not exceed 255 characters.',
            'unique' => 'This field must be unique.',
            'integer' => 'This field must be an integer.',
            'icon.max' => 'The size of the image is too big.',
        ];
    }
}
