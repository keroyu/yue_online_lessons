<?php

namespace App\Http\Requests\Admin;

use App\Http\Controllers\Admin\HomepageSettingController;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCourseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Validate content_category against the admin-configured category slugs;
     * fall back to a slug-format check when none are configured.
     */
    protected function contentCategoryRule(): array
    {
        $slugs = array_column(HomepageSettingController::contentCategories(), 'slug');

        return $slugs
            ? ['required', Rule::in($slugs)]
            : ['required', 'string', 'max:50', 'regex:/^[a-z-]+$/'];
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
            'slug' => ['nullable', 'string', 'max:200', 'unique:courses,slug', 'regex:/^[a-z0-9\-]+$/'],
            'tagline' => ['required', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:160'],
            'description' => ['required', 'string'],
            'description_md' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'redeem_points' => ['nullable', 'integer', 'min:0'],
            'original_price' => ['nullable', 'integer', 'min:0'],
            'promo_ends_at' => ['nullable', 'date', 'after:now'],
            'thumbnail' => ['nullable', 'image', 'max:10240'], // 10MB
            'instructor_name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'in:lecture,mini,full,high_ticket'],
            'content_category' => $this->contentCategoryRule(),
            'duration_minutes' => ['nullable', 'integer', 'min:0'],
            'sale_at' => ['nullable', 'date', 'after:now'],
            'portaly_product_id' => ['nullable', 'string', 'max:100'],
            'payment_gateway' => ['nullable', 'string', 'in:payuni,newebpay'],
            'is_visible' => ['nullable', 'boolean'],
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
            'type.required' => '請選擇產品類型',
            'type.in' => '產品類型無效',
            'content_category.required' => '請選擇內容分類',
            'content_category.in' => '內容分類無效',
            'content_category.regex' => '內容分類無效',
            'duration_minutes.integer' => '時間總長必須是整數',
            'duration_minutes.min' => '時間總長不能為負數',
            'sale_at.after' => '開賣時間必須在未來',
            'original_price.integer' => '原價必須是整數',
            'original_price.min' => '原價不能為負數',
            'promo_ends_at.date' => '優惠到期時間格式不正確',
            'promo_ends_at.after' => '優惠到期時間必須在未來',
        ];
    }
}
