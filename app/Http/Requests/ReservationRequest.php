<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReservationRequest extends FormRequest
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
            'reservation_date' => 'required|date_format:Y-m-d H:i:s',
            'pax' => 'required|string|max:255',
            'name' => ['required', 'max:255', function ($attribute, $value, $fail) {
                if (!is_string($value) && !is_numeric($value)) {
                    $fail('This field must be a string or number.');
                }
            }],
            'phone' => 'required|string|max:255',
            'table_no' => 'required',
            'lock_before_minutes' => 'required|integer',
            'grace_period' => 'required|integer',
        ];
    }

    public function attributes(): array
    {
        return [
            'reservation_date' => 'Reservation date',
            'pax' => 'Pax',
            'name' => 'Name',
            'phone' => 'Contact no.',
            'table_no' => 'Table',
            'lock_before_minutes' => 'Table Lock Before',
            'grace_period' => 'Grace period',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'This field is required.',
            'string' => 'This field must be a string.',
            'max' => 'This field must not exceed 255 characters.',
            'integer' => 'This field must be an integer.',
        ];
    }
}
