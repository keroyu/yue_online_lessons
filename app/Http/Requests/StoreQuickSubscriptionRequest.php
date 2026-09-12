<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Homepage hero subscription — no verification code (002 US21 / 012 FR-015).
 *
 * Deliberately a separate request from StoreNewsletterSubscriptionRequest: the
 * OTP path must not become skippable by anything a caller sends (002 D60). With
 * the code gone, what stands here is the honeypot plus the route's throttle.
 *
 * `email:rfc` only — `dns` would do a synchronous lookup on every submit: slow,
 * a false negative whenever the network hiccups, and a test suite that needs the
 * internet. The typo it would catch is handled by the review step on the form,
 * which puts the address in front of the person who typed it (002 D62).
 */
class StoreQuickSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'    => ['required', 'email:rfc', 'max:255'],
            'nickname' => ['required', 'string', 'max:50', 'regex:/\p{L}/u'],
            // Hidden from people, irresistible to bots.
            'website'  => ['nullable', 'prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required'     => '請輸入 Email',
            'email.email'        => '請輸入有效的 Email 格式',
            'nickname.required'  => '請輸入暱稱',
            'nickname.regex'     => '暱稱需包含至少一個文字（不可為純空格或符號）',
            'website.prohibited' => '訂閱失敗',
        ];
    }
}
