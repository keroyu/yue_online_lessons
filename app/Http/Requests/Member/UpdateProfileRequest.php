<?php

namespace App\Http\Requests\Member;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nickname' => ['nullable', 'string', 'max:100'],
            'real_name' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
            'birth_date' => ['nullable', 'date', 'before:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'nickname.max' => '暱稱最多 100 個字元',
            'real_name.max' => '真實姓名最多 100 個字元',
            'phone.max' => '電話最多 20 個字元',
            'birth_date.date' => '出生日期格式不正確',
            'birth_date.before' => '出生日期必須在今天之前',
        ];
    }
}
