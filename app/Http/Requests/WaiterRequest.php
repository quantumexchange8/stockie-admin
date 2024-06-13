<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WaiterRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'phone' => ['required', 'string', 'regex:/^\+?[0-9]{7,15}$/'],
            'email' => 'required|email|unique:waiters,email',
            'staffid' => 'required|string|unique:waiters,staffid',
            'salary' => 'required|integer|min:0',
            'stockie_email' => 'required|email',
            'stockie_password' => 'required|string',
        ];
    }


    public function messages()
    {
        return [
            'email.unique' => 'The email has already been taken.',
            'staffid.unique' => 'The staff ID has already been taken.',
            'phone.regex' => 'Invalid phone number',
            'salary.integer' => 'The salary must be an integer value.',
            'salary.min' => 'The salary must be at least 0.',
            'stockie_email.email' => 'The stockie email must be a valid email address.',

        ];
    }

}
