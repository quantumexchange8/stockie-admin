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
            'full_name'=>'required|string|max:255',
            'phone' => 'required|string|max:255',
            'salary' => 'required|string|min:0',
            'stockie_email' => 'required|email',
            'image' => 'required',
        ];

        $rules['password'] = [
            $this->input('id') ? 'nullable' : 'required',
            'string'
        ];

        $rules['email'] = [
            'required',
            'email',
            $this->input('id') 
                    ? Rule::unique('users')->ignore($this->input('id'))->whereNull('deleted_at')
                    : 'unique:users,email,NULL,id,deleted_at,NULL'
        ];

        $rules['role_id'] = [
            'required',
            'string',
            $this->input('id')
                    ? Rule::unique('users')->ignore($this->input('id'))->whereNull('deleted_at')
                    : 'unique:users,role_id,NULL,id,deleted_at,NULL'
        ];

        $rules['passcode'] = [
            'nullable',
            'integer',
            $this->input('passcode') != '' ? 'min_digits:6' : '',
            'max_digits:6',
            $this->input('id') 
                    ? Rule::unique('users')->ignore($this->input('id'))->whereNull('deleted_at') 
                    : 'unique:users,passcode,NULL,id,deleted_at,NULL',
            
        ];

        return $rules;
    }


    public function messages()
    {
        return [
            'email.unique' => 'Email has already been taken.',
            'email.email' => 'Invalid email.',
            'role_id.unique' => 'Staff ID has already been taken.',
            // 'phone.required' => 'This field is required.',
            // 'salary.string' => 'Salary must be an decimal value.',
            'salary.min' => 'Salary must be at least 0.',
            'worker_email.email' => 'Invalid email',
            'image.required' => 'Image is required.',
            'required'=> 'This field is required.',
        ];
    }

}
