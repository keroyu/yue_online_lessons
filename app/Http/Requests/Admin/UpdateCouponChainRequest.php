<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCouponChainRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $chainId = $this->route('coupon_chain')?->id;

        return [
            'alias'         => ['required', 'string', 'max:50', 'regex:/^[a-zA-Z0-9_]+$/', "unique:coupon_chains,alias,{$chainId}"],
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
            'alias.regex'  => '別名只能使用英數字與底線',
            'alias.unique' => '此別名已被使用',
        ];
    }

    private function valueRule(): string
    {
        return $this->input('type') === 'ratio' ? 'between:0.5,0.95' : 'min:10';
    }
}
