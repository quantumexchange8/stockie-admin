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
            'ranking_id' => 'required|integer',
            'reward_type' => 'required|string|max:255',
            'min_purchase' => 'required|string|max:255',
            'amount' => 'nullable|string|max:255',
            'min_purchase_amount' => 'nullable|integer',
            'bonus_point' => 'nullable|string|max:255',
            'free_item' => 'nullable|string|max:255',
            'item_qty' => 'nullable|string|max:255',
            'valid_period_from' => 'nullable|date_format:Y-m-d H:i:s',
            'valid_period_to' => 'nullable|date_format:Y-m-d H:i:s',
        ];
    }

    public function attributes(): array
    {
        return [
            'ranking_id' => 'Ranking',
            'reward_type' => 'Reward Type',
            'min_purchase' => 'Minimum Purchase',
            'amount' => 'Amount',
            'min_purchase_amount' => 'Minimum Purchase Amount',
            'bonus_point' => 'Bonus Point',
            'free_item' => 'Item',
            'item_qty' => 'Item Quantity',
            'valid_period_from' => 'Valid Period Start',
            'valid_period_to' => 'Valid Period End',
        ];
    }

    public function messages(): array
    {
        return [
            'ranking_id.required' => 'This field is required.',
            'ranking_id.integer' => 'This field must be an integer.',
            'reward_type.required' => 'This field is required.',
            'reward_type.string' => 'This field must be a string.',
            'reward_type.max' => 'This field must not exceed 255 characters.',
            'min_purchase.required' => 'This field is required.',
            'min_purchase.string' => 'This field must be a string.',
            'min_purchase.max' => 'This field must not exceed 255 characters.',
            'amount.string' => 'This field must be a string.',
            'amount.max' => 'This field must not exceed 255 characters.',
            'min_purchase_amount.string' => 'This field must be a string.',
            'min_purchase_amount.max' => 'This field must not exceed 255 characters.',
            'bonus_point.string' => 'This field must be a string.',
            'bonus_point.max' => 'This field must not exceed 255 characters.',
            'free_item.string' => 'This field must be a string.',
            'free_item.max' => 'This field must not exceed 255 characters.',
            'item_qty.string' => 'This field must be a string.',
            'item_qty.max' => 'This field must not exceed 255 characters.',
            'valid_period_from.date_format' => 'This field must be a date with time.',
            'valid_period_to.date_format' => 'This field must be a date with time.',
        ];
    }
}
