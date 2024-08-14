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
            // 'icon' => 'required|string|max:255',
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
            'name' => 'Ranking Name',
            'min_amount' => 'Minimum Amount',
            'reward' => 'Reward',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'This field is required.',
            'name.string' => 'This field must be a string.',
            'name.max' => 'This field must not exceed 255 characters.',
            'name.unique' => 'This field must be unique.',
            'min_amount.required' => 'This field is required.',
            'min_amount.integer' => 'This field must be an integer.',
            'reward.required' => 'This field is required.',
            'reward.string' => 'This field must be a string.',
            'reward.max' => 'This field must not exceed 255 characters.',
        ];
    }
}
