<?php

namespace App\Http\Requests\Admin;

use App\Services\VideoEmbedService;
use Illuminate\Foundation\Http\FormRequest;

class StoreLessonRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'chapter_id' => ['nullable', 'exists:chapters,id'],
            'title' => ['required', 'string', 'max:255'],
            'video_url' => ['nullable', 'url', 'max:500'],
            'html_content' => ['nullable', 'string'],
            'duration_seconds' => ['nullable', 'integer', 'min:0'],
            'promo_delay_seconds' => ['nullable', 'integer', 'min:0', 'max:7200'],
            'promo_html' => ['nullable', 'string', 'max:10000'],
            'reward_html' => ['nullable', 'string', 'max:10000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'chapter_id.exists' => '指定的章節不存在',
            'title.required' => '請輸入小節標題',
            'title.max' => '小節標題不能超過 255 字',
            'video_url.url' => '影片連結格式無效',
            'video_url.max' => '影片連結太長',
            'duration_seconds.integer' => '時長必須是整數',
            'duration_seconds.min' => '時長不能為負數',
            'promo_delay_seconds.integer' => '延遲時間必須是整數',
            'promo_delay_seconds.min' => '延遲時間不能為負數',
            'promo_delay_seconds.max' => '延遲時間不能超過 7200 秒',
            'promo_html.max' => '促銷內容太長',
            'reward_html.max' => '獎勵內容太長',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->video_url) {
                $service = new VideoEmbedService();
                if (!$service->isValid($this->video_url)) {
                    $validator->errors()->add('video_url', '影片連結必須是有效的 Vimeo 或 YouTube 連結');
                }
            }
        });
    }
}
