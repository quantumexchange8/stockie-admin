<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TableRoomRequest extends FormRequest
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
            'type'=> 'required',
            'table_no'=>'required|string|max:255|unique:tables',
            'seat'=> 'required|numeric',
            'zone_id' => 'required|numeric',
        ];
    }

    public function attributes(): array
    {
        return [
            'type'=> 'Add Table / Room',
            'table_no' => 'Table / Room No.',
            'seat' => 'No. of Seats Available',
            'zone_id'=> 'Select Zone',
        ];
    }

    public function messages(): array
    {
        return [
            'type.required'=> 'This field is required.',
            'table_no.required' => 'This field is required.',
            'table_no.string' => 'This field must be a string.',
            'table_no.max' => 'This field must not exceed 255 characters.',
            'table_no.unique' => 'Table / Room No. already exists, please try another.',
            'seat.required' => 'This field is required.',
            'seat.numeric' => 'This field must be numeric.',
            'zone_id.required' => 'This field is required.',
            'zone_id.numeric' => 'This field must be numeric',
        ];
    }
}
