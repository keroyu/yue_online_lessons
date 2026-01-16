<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class VerifyCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'code' => ['required', 'string', 'size:6'],
            'agree_terms' => ['sometimes', 'accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => '請輸入 Email',
            'email.email' => '請輸入有效的 Email 格式',
            'code.required' => '請輸入驗證碼',
            'code.size' => '驗證碼必須是 6 位數字',
            'agree_terms.accepted' => '請同意服務條款和隱私政策',
        ];
    }
}
