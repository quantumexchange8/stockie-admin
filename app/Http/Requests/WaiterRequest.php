<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WaiterRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'phone' => preg_replace('/\s+/', '', $this->phone),
        ]);
    }

    public function rules()
    {
        $rules = [
            'username'=>'required|string|max:255',
            'name'=>'required|string|max:255',
            'phone' => ['required', 'string', 'regex:/^\+?[0-9]{7,15}$/'],
            // 'email' => 'required|email|unique:waiters',
            // 'staffid' => 'required|string|unique:waiters,staffid',
            'salary' => 'required|integer|min:0',
            'stockie_email' => 'required|email',
            'password' => 'required|string',
        ];

        $rules['email'] = $this->input('id') 
                ?
                    [
                        'required',
                        'email',
                        Rule::unique('users')->ignore($this->input('id')),
                    ]
                : 'required|email|unique:users';


        $rules['role_id'] = $this->input('id') 
                ?    
                    [
                        'required',
                        'string',
                        Rule::unique('users')->ignore($this->input('id')),
                    ]
                : 'required|string|unique:users';

        return $rules;
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
