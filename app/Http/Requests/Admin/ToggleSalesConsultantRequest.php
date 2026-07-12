<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ToggleSalesConsultantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route sits in the admin-only middleware group
    }

    public function rules(): array
    {
        return [
            'is_sales_consultant' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'is_sales_consultant.required' => '缺少銷售顧問狀態',
            'is_sales_consultant.boolean'  => '銷售顧問狀態格式錯誤',
        ];
    }
}
