<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Guest claim of a free drip product (010 US15).
 *
 * The verification code used to be the thing standing between this endpoint and
 * "type any address, we mail it" — with the code gone, the honeypot and the
 * route's throttle are what remain (FR-026). Every letter sent to an address
 * nobody asked for is charged to our own sending reputation.
 */
class StoreDripClaimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'course_id' => ['required', 'exists:courses,id'],
            'email'     => ['required', 'email'],
            'nickname'  => ['required', 'string', 'max:50', 'regex:/\p{L}/u'],
            // Hidden from people, irresistible to bots.
            'website'   => ['nullable', 'prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'course_id.required' => '請選擇課程',
            'course_id.exists'   => '課程不存在',
            'email.required'     => '請輸入 Email',
            'email.email'        => '請輸入有效的 Email 格式',
            'nickname.required'  => '請輸入暱稱',
            'nickname.regex'     => '暱稱需包含至少一個文字（不可為純空格或符號）',
            'website.prohibited' => '領取失敗',
        ];
    }
}
