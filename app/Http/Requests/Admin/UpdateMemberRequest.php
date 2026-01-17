<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMemberRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => [
                'sometimes',
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->route('member')),
            ],
            'real_name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'nickname' => ['sometimes', 'nullable', 'string', 'max:100'],
            'birth_date' => ['sometimes', 'nullable', 'date', 'before_or_equal:today'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'email.required' => '請輸入 Email',
            'email.email' => 'Email 格式不正確',
            'email.max' => 'Email 不能超過 255 字',
            'email.unique' => '此 Email 已被使用',
            'real_name.max' => '姓名不能超過 100 字',
            'phone.max' => '電話不能超過 20 字',
            'nickname' => '暱稱不能超過 100 字',
            'birth_date.date' => '生日格式不正確',
            'birth_date.before_or_equal' => '生日不能是未來日期',
        ];
    }
}
