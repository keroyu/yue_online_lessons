<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateHomepageSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'hero_title'          => ['nullable', 'string', 'max:255'],
            'hero_description'    => ['nullable', 'string', 'max:2000'],
            'hero_button_label'   => ['nullable', 'string', 'max:100'],
            'hero_button_url'     => ['nullable', 'url', 'max:500'],
            'hero_banner'         => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:min_width=1200'],
            'blog_rss_url'        => ['nullable', 'url', 'max:500'],
            'sns_section_enabled' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'hero_banner.image'      => '請上傳圖片檔案',
            'hero_banner.mimes'      => '圖片格式必須是 jpg、jpeg、png 或 webp',
            'hero_banner.max'        => '圖片大小不能超過 5MB',
            'hero_banner.dimensions' => '圖片寬度至少需要 1200px',
        ];
    }

    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function ($validator) {
            // PHP silently drops files that exceed upload_max_filesize.
            // $_FILES still records the error code — catch it here so the
            // user gets a clear message instead of a silent no-op.
            $fileError = $_FILES['hero_banner']['error'] ?? UPLOAD_ERR_OK;
            if ($fileError === UPLOAD_ERR_INI_SIZE || $fileError === UPLOAD_ERR_FORM_SIZE) {
                $validator->errors()->add('hero_banner', '圖片檔案過大，請壓縮後再上傳（上限 5MB）');
            }
        });
    }
}
