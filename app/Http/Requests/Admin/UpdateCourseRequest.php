<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCourseRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'tagline' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'description_html' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'original_price' => ['nullable', 'integer', 'min:0'],
            'promo_ends_at' => ['nullable', 'date'],
            'thumbnail' => ['nullable', 'image', 'max:10240'], // 10MB
            'instructor_name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'in:lecture,mini,full'],
            'duration_minutes' => ['nullable', 'integer', 'min:0'],
            'sale_at' => ['nullable', 'date'],
            'portaly_product_id' => ['nullable', 'string', 'max:100'],
            'is_visible' => ['nullable', 'boolean'],
            'course_type' => ['required', 'in:standard,drip'],
            'drip_interval_days' => ['nullable', 'required_if:course_type,drip', 'integer', 'min:1', 'max:30'],
            'target_course_ids' => ['nullable', 'array'],
            'target_course_ids.*' => ['exists:courses,id'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => '請輸入課程名稱',
            'name.max' => '課程名稱不能超過 255 字',
            'tagline.required' => '請輸入課程副標題',
            'description.required' => '請輸入課程描述',
            'price.required' => '請輸入課程價格',
            'price.min' => '課程價格不能為負數',
            'thumbnail.image' => '縮圖必須是圖片格式',
            'thumbnail.max' => '縮圖大小不能超過 10MB',
            'instructor_name.required' => '請輸入講師名稱',
            'type.required' => '請選擇課程類型',
            'type.in' => '課程類型無效',
            'duration_minutes.integer' => '時間總長必須是整數',
            'duration_minutes.min' => '時間總長不能為負數',
            'original_price.integer' => '原價必須是整數',
            'original_price.min' => '原價不能為負數',
            'promo_ends_at.date' => '優惠到期時間格式不正確',
            'course_type.required' => '請選擇課程模式',
            'course_type.in' => '課程模式無效',
            'drip_interval_days.required_if' => '連鎖課程需設定發信間隔天數',
            'drip_interval_days.integer' => '發信間隔天數必須是整數',
            'drip_interval_days.min' => '發信間隔天數至少為 1 天',
            'drip_interval_days.max' => '發信間隔天數不能超過 30 天',
            'target_course_ids.array' => '目標課程格式無效',
            'target_course_ids.*.exists' => '選擇的目標課程不存在',
        ];
    }
}
