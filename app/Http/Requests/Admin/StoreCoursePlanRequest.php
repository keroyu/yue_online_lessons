<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreCoursePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50'],
            // Suggested deal price only — it prefills the conversion modal and
            // never appears on the sales page (011 D79).
            'price' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => '請填寫方案名稱',
            'name.max' => '方案名稱不可超過 50 個字',
            'price.integer' => '建議價格必須是整數',
            'price.min' => '建議價格不可為負數',
        ];
    }
}
