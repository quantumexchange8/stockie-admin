<?php

namespace App\Http\Requests;

use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class CustomerRequest extends FormRequest
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
            'password' => [
                'required',
                'string',
                // 'exists:customers,password',
            ],
        ];
        return $rules;
    }

    public function messages()
    {
        return [
            'verification_code.required' => 'This field is required',
            'verification_code.integer' => 'This field must be an string.',
            // 'verification_code.exists'=> 'Invalid verification code.',
        ];
    }
}
