<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 011 FR-241 — the public testimonial form. No auth: the token in the URL is
 * the credential, resolved by the controller.
 */
class StoreTestimonialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // input(), not $this->content: on a Request that property is the raw body.
        $trim = fn (string $key) => is_string($value = $this->input($key)) ? trim($value) : $value;

        $this->merge([
            'nickname' => $trim('nickname'),
            'title'    => $trim('title') === '' ? null : $trim('title'),
            'content'  => $trim('content'),
        ]);
    }

    public function rules(): array
    {
        // Laravel's string min/max count characters (mb_strlen), so CJK text
        // gets the same limits as the UI promises.
        return [
            'nickname' => ['required', 'string', 'max:20'],
            'title'    => ['nullable', 'string', 'max:40'],
            'content'  => ['required', 'string', 'min:10', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'nickname.required' => '請填寫暱稱',
            'nickname.max'      => '暱稱最多 20 字',
            'title.max'         => '頭銜最多 40 字',
            'content.required'  => '請填寫評語',
            'content.min'       => '評語至少 10 個字',
            'content.max'       => '評語最多 500 字',
        ];
    }
}
