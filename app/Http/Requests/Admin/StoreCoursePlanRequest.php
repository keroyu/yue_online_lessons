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
            // Bundle credits this tier grants on a sale (011 US37 / FR-202).
            // Edited on the course settings page, saved through this endpoint.
            'bundle_quantity' => ['nullable', 'integer', 'min:0'],
            // Uncapped perk for this tier (011 US37 / FR-213); when true the
            // quantity above is ignored everywhere.
            'bundle_unlimited' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => '請填寫方案名稱',
            'name.max' => '方案名稱不可超過 50 個字',
            'price.integer' => '建議價格必須是整數',
            'price.min' => '建議價格不可為負數',
            'bundle_quantity.integer' => '福利次數必須是整數',
            'bundle_quantity.min' => '福利次數不可為負數',
        ];
    }
}
