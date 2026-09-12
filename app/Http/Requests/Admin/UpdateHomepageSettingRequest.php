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
            'hero_subtitle'       => ['nullable', 'string', 'max:255'],
            'hero_description'    => ['nullable', 'string', 'max:2000'],
            // No `dimensions` rule: the hero image is now the right-hand
            // portrait, and a de-bleached PNG of a person is routinely
            // narrower than the 1200px a full-bleed banner needed (FR-054).
            'hero_banner'         => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            // Drives the 📌 line under the hero only — the subscribe form is
            // no longer tied to it (FR-070). Still a bare reference with no
            // foreign key behind it, so every read re-checks.
            'hero_promo_course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'blog_rss_url'        => ['nullable', 'url', 'max:500'],
            'sns_section_enabled' => ['required', 'boolean'],
            'sns_profile_intro'   => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'hero_banner.image'      => '請上傳圖片檔案',
            'hero_banner.mimes'      => '圖片格式必須是 jpg、jpeg、png 或 webp',
            'hero_banner.max'        => '圖片大小不能超過 5MB',
            'hero_promo_course_id.exists' => '選擇的課程不存在',
            'sns_profile_intro.max'  => '站長介紹不能超過 500 字',
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
