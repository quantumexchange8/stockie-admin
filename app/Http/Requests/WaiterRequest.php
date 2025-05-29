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
            'employment_type' => 'required|string',
            'salary' => 'required|string|min:0',
            'position_id' => 'required|integer',
            'stockie_email' => 'required|email',
            'image' => 'required|max:8000',
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
            'salary.min' => 'Salary must be at least 0.',
            'position.integer' => 'Position id must be an integer value.',
            'worker_email.email' => 'Invalid email',
            'image.required' => 'Image is required.',
            'image.max' => 'The size of the image is too big.',
            'required'=> 'This field is required.',
        ];
    }

}
