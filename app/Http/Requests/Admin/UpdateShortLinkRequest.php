<?php

namespace App\Http\Requests\Admin;

use App\Models\ShortLink;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateShortLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('slug')) {
            $this->merge(['slug' => mb_strtolower(trim((string) $this->input('slug')))]);
        }
    }

    public function rules(): array
    {
        return [
            'slug' => [
                'required', 'string', 'max:64', 'regex:/^[a-z0-9_-]+$/',
                Rule::unique('short_links', 'slug')->ignore($this->route('short_link')),
                Rule::notIn(ShortLink::reservedSlugs()),
            ],
            'target_url' => ['required', 'url:http,https', 'max:2048'],
            'name'       => ['nullable', 'string', 'max:100'],
            'is_active'  => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.required'       => '請填入短網址代稱',
            'slug.regex'          => '短網址只能使用英文、數字、連字號與底線',
            'slug.unique'         => '這個短網址已經有人用了',
            'slug.not_in'         => '這個代稱與網站現有路徑衝突，請換一個',
            'target_url.required' => '請填入目標網址',
            'target_url.url'      => '請填入有效的網址（http:// 或 https:// 開頭）',
        ];
    }
}
