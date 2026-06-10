<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCouponRequest extends FormRequest
{
    /**
     * Admin middleware handles authorization.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * `code` 不開放修改（永久唯一），故不納入更新驗證。
     */
    public function rules(): array
    {
        return [
            'type'       => ['required', 'in:fixed,ratio'],
            'value'      => array_merge(['required', 'numeric'], $this->valueRule()),
            'course_id'  => ['nullable', 'exists:courses,id'],
            'expires_at' => ['nullable', 'date'],
            'max_uses'   => ['nullable', 'integer', 'min:1'],
            'is_active'  => ['boolean'],
            'note'       => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function valueRule(): array
    {
        return $this->input('type') === 'ratio'
            ? ['between:0.50,0.95']
            : ['min:10'];
    }

    public function messages(): array
    {
        return [
            'type.required'    => '請選擇折扣類型',
            'type.in'          => '折扣類型無效',
            'value.required'   => '請輸入折扣值',
            'value.numeric'    => '折扣值須為數字',
            'value.min'        => '最低折抵金額為 NT$10',
            'value.between'    => '折數須介於 0.50 至 0.95 之間',
            'course_id.exists' => '指定課程不存在',
            'max_uses.min'     => '使用名額至少為 1',
        ];
    }
}
