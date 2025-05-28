<?php

namespace App\Http\Requests;

use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        $rules['phone'] = [
            'nullable',
            'string',
            'max:255',
            $this->input('id') 
                    ? Rule::unique('customers')->ignore($this->input('id'))->whereNull('deleted_at')
                    : 'unique:customers,phone'
        ];

        $rules['password'] = [
            $this->input('id') ? 'nullable' : 'required',
            'string'
        ];

        $rules['full_name'] = [
            'required',
            'string',
            'max:255',
            $this->input('id') 
                    ? Rule::unique('customers')->ignore($this->input('id'))->whereNull('deleted_at')
                    : 'unique:customers,full_name'
        ];

        $rules['email'] = [
            'nullable',
            'email',
            $this->input('id') 
                    ? Rule::unique('customers')->ignore($this->input('id'))->whereNull('deleted_at')
                    : 'unique:customers,email'
        ];

        return $rules;
    }


    public function messages()
    {
        return [
            'email.unique' => 'Email has already been taken.',
            'email.email' => 'Invalid email.',
            'required' => 'This field is required.',
            'email' => 'Invalid email',
        ];
    }
}
