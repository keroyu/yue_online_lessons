<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

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
            'body_md' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => '請輸入模板名稱',
            'subject.required' => '請輸入郵件主旨',
            'body_md.required' => '請輸入郵件內容',
        ];
    }
}
