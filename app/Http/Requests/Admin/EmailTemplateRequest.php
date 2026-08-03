<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmailTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'subject' => ['required', 'string', 'max:255'],
            'body_type' => ['required', Rule::in(['markdown', 'html'])],
            'body_md' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => '請輸入模板名稱',
            'subject.required' => '請輸入郵件主旨',
            'body_type.required' => '請選擇內容格式',
            'body_type.in' => '內容格式只能是 Markdown 或 HTML',
            'body_md.required' => '請輸入郵件內容',
        ];
    }
}
