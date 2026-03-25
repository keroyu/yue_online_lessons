<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSocialLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'url' => ['required', 'url', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'url.required' => '請填入網址',
            'url.url'      => '請填入有效的網址（包含 https://）',
        ];
    }
}
