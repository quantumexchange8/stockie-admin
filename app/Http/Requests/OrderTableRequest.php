<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OrderTableRequest extends FormRequest
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
            'table_id' => 'required|integer',
            'pax' => 'required|string|max:255',
            'waiter_id' => 'nullable|integer',
            'status' => 'required|string|max:255',
            'reservation_date' => 'nullable|date_format:Y-m-d H:i:s',
            'order_id' => 'nullable|integer',
        ];
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
