<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePurchasePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            // null is legal and means "back to the whole course" (011 FR-087).
            'course_plan_id' => ['nullable', 'integer', 'exists:course_plans,id'],
            // Money the member wired for the upgrade. Added to the purchase,
            // never subtracted — refunds go through the transactions admin.
            'additional_amount' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'additional_amount.min' => '補價金額不可為負數，退款請至交易管理處理',
            'additional_amount.integer' => '補價金額必須是整數',
        ];
    }
}
