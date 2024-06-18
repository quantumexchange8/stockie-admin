<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfigPromotionRequest extends FormRequest
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
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'promotions_from' => 'required|date_format:d/m/Y',
            'promotions_to' => 'required|date_format:d/m/Y',
            'image' => 'required|string|max:255',
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'Title',
            'description' => 'Description',
            'promotions_from' => 'Promotions from',
            'promotions_to' => 'Promotions to',
            'image' => 'Image',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'The title field is required.',
            'title.string' => 'The title field must be a string.',
            'title.max' => 'The title field must not exceed 244 characters.',
            'description.required' => 'The description field is required.',
            'description.string' => 'The description field must be a string.',
            'promotions_from.required' => 'The promotions_from field is required.',
            'promotions_from.date_format' => 'The promotions_from field has an invalid format.',
            'promotions_to.required' => 'The promotions_to field is required.',
            'promotions_to.date_format' => 'The promotions_to field has an invalid format.',
            'image.required' => 'The image field is required.',
            'image.string' => 'The image field must be a string.',
            'image.max' => 'The image field must not exceed 244 characters.',
        ];
    }
}
