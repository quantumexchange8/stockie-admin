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
            'tables' => 'required|array',
            'pax' => 'required|string|max:255',
            'assigned_waiter' => 'required|integer',
            'order_id' => 'nullable|integer',
        ];
    }

    public function attributes(): array
    {
        return [
            'tables' => 'Tables',
            'pax' => 'No. of pax',
            'assigned_waiter' => 'Assigned Waiter',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'This field is required.',
            'string' => 'This field must be a string.',
            'max' => 'This field must not exceed 255 characters.',
            'integer' => 'This field must be an integer.',
            'array' => 'This field must be an array.',
        ];
    }
}
