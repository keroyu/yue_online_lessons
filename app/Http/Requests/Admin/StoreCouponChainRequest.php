<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreCouponChainRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'alias'         => ['required', 'string', 'max:50', 'regex:/^[a-zA-Z0-9_]+$/', 'unique:coupon_chains,alias'],
            'course_id'     => ['nullable', 'integer', 'exists:courses,id'],
            'type'          => ['required', 'in:fixed,ratio'],
            'value'         => ['required', 'numeric', $this->valueRule()],
            'code_max_uses' => ['required', 'integer', 'min:0'],
            'is_active'     => ['boolean'],
            'note'          => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'alias.regex'       => '別名只能使用英數字與底線',
            'alias.unique'      => '此別名已被使用',
            'value.min'         => $this->input('type') === 'ratio'
                ? '折數須介於 0.50 至 0.95 之間'
                : '最低折抵金額為 NT$10',
            'value.max'         => '折數須介於 0.50 至 0.95 之間',
        ];
    }

    private function valueRule(): string
    {
        return $this->input('type') === 'ratio' ? 'between:0.5,0.95' : 'min:10';
    }
}
