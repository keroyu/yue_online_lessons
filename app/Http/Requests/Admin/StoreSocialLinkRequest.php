<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreSocialLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'platform' => ['required', 'string', 'in:instagram,threads,youtube,facebook,substack,podcast'],
            'url'      => ['required', 'url', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'platform.in'  => '請選擇有效的社群平台',
            'url.required' => '請填入網址',
            'url.url'      => '請填入有效的網址（包含 https://）',
        ];
    }
}
