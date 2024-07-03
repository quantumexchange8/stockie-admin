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
            'full_name'=>'required|string|max:255',
            'name'=>'required|string|max:255',
            'phone' => ['required', 'string', 'regex:/^\+?[0-9]{7,15}$/'],
            'email' => 'required|email|unique:users,email',
            'role_id' => 'required|string|unique:users,role_id',
            'salary' => 'required|integer|min:0',
            'worker_email' => 'required|email',
            'password' => 'required|string',
        ];
    }


    public function messages()
    {
        return [
    
            'email.unique' => 'Email has already been taken.',
            'email.email' => 'Invalid email.',
            'role_id.unique' => 'Staff ID has already been taken.',
            'phone.regex' => 'Invalid phone number',
            'salary.integer' => 'Salary must be an integer value.',
            'salary.min' => 'Salary must be at least 0.',
            'worker_email.email' => 'Invalid email',

        ];
    }

}
