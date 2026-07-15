<?php

namespace App\Http\Requests\Member;

use App\Models\UserSocialLink;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreUserSocialLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'platform' => ['required', 'string', Rule::in(UserSocialLink::PLATFORMS)],
            'url'      => ['required', 'url', 'starts_with:https://', 'max:500'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->user()->socialLinks()->count() >= UserSocialLink::MAX_PER_USER) {
                $validator->errors()->add('url', '最多只能新增 '.UserSocialLink::MAX_PER_USER.' 個社群連結');
            }
        });
    }

    public function messages(): array
    {
        return [
            'platform.in'     => '請選擇有效的社群平台',
            'url.required'    => '請填入網址',
            'url.url'         => '請填入有效的網址（包含 https://）',
            'url.starts_with' => '網址必須以 https:// 開頭',
        ];
    }
}
