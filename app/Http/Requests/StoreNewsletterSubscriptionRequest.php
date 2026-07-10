<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreNewsletterSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            // Honeypot: bots fill this hidden field; humans leave it empty.
            'website' => ['nullable', 'prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => '請輸入 Email',
            'email.email' => '請輸入有效的 Email 格式',
            'website.prohibited' => '訂閱失敗',
        ];
    }
}
