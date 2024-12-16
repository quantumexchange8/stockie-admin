<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RankingRewardRequest extends FormRequest
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
            // 'ranking_id' => 'required|integer',
            'reward_type' => 'required|string|max:255',
            'min_purchase' => 'required|string|max:255',
            'min_purchase_amount' => 'nullable|integer',
            'discount' => 'nullable|string|max:255',
            'bonus_point' => 'nullable|string|max:255',
            'free_item' => 'nullable|integer',
            'item_qty' => 'nullable|string|max:255',
            // 'valid_period_from' => 'nullable|date_format:Y-m-d H:i:s',
            // 'valid_period_to' => 'nullable|date_format:Y-m-d H:i:s',
            'status' => 'required|string|max:255',
        ];
    }

    public function attributes(): array
    {
        return [
            'ranking_id' => 'Ranking',
            'reward_type' => 'Reward Type',
            'min_purchase' => 'Minimum Purchase',
            'discount' => 'Amount',
            'min_purchase_amount' => 'Minimum Purchase Amount',
            'bonus_point' => 'Bonus Point',
            'free_item' => 'Item',
            'item_qty' => 'Item Quantity',
            // 'valid_period_from' => 'Valid Period Start',
            // 'valid_period_to' => 'Valid Period End',
            'status' => 'Status',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'This field is required.',
            'integer' => 'This field must be an integer.',
            'string' => 'This field must be a string.',
            'max' => 'This field must not exceed 255 characters.',
            'date_format' => 'This field must be a date with time.',
        ];
    }
}
