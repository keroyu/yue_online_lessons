<?php

namespace App\Http\Requests\Admin;

use App\Http\Controllers\Admin\HomepageSettingController;
use App\Http\Requests\Concerns\NormalizesTaipeiInput;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCourseRequest extends FormRequest
{
    use NormalizesTaipeiInput;

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
     * Drip courses have no pricing card in the form, so no price is posted.
     * Default it here instead of relying on the client to send a hidden zero.
     */
    protected function prepareForValidation(): void
    {
        // Ahead of the drip early-return below: both course types post these.
        $this->readAsTaipei('sale_at', 'promo_ends_at');

        if ($this->input('course_type') === 'drip') {
            $this->merge(['price' => 0]);

            return;
        }

        // A standard course has no send schedule. The form posts every field it
        // holds regardless of course type, so without this the drip rules run
        // on a course that will never send an email — and a course with
        // chapters fails them outright, because each chapter restarts
        // sort_order at 1 and the posted rows no longer line up with the
        // running order the check reads (010 FR-037).
        $this->merge(['drip_days' => null]);
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
            'slug' => ['nullable', 'string', 'max:200', 'unique:courses,slug,' . $this->route('course')->id, 'regex:/^[a-z0-9\-]+$/'],
            'tagline' => ['required', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:160'],
            'description' => ['required', 'string'],
            'description_md' => ['nullable', 'string'],
            'free_success_md' => ['nullable', 'string', 'max:5000'],
            'promo_html' => ['nullable', 'string', 'max:5000'],
            'promo_delay_seconds' => ['nullable', 'integer', 'min:0', 'max:86400'],
            'price' => ['required_unless:course_type,drip', 'numeric', 'min:0'],
            'redeem_points' => ['nullable', 'integer', 'min:0'],
            // Bundle perk sold with a high-ticket course (011 US37 / FR-201).
            // The name is the switch: empty means this course has no perk.
            'bundle_name' => ['nullable', 'string', 'max:50'],
            'bundle_redeem_points' => ['nullable', 'integer', 'min:1'],
            'bundle_default_quantity' => ['nullable', 'integer', 'min:0'],
            'bundle_unlimited' => ['nullable', 'boolean'],
            // Per-tier quantities ride with this form rather than having their
            // own save button — the page promises one save (011 D145 revised).
            'bundle_plans' => ['nullable', 'array'],
            'bundle_plans.*.quantity' => ['nullable', 'integer', 'min:0'],
            'bundle_plans.*.unlimited' => ['nullable', 'boolean'],
            'original_price' => ['nullable', 'integer', 'min:0'],
            'promo_ends_at' => ['nullable', 'date'],
            'thumbnail' => ['nullable', 'image', 'max:10240'], // 10MB
            'instructor_name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'in:lecture,mini,full,high_ticket,ebook'],
            'content_category' => $this->contentCategoryRule(),
            'high_ticket_hide_price' => ['nullable', 'boolean'],
            'duration_minutes' => ['nullable', 'integer', 'min:0'],
            'sale_at' => ['nullable', 'date'],
            'portaly_product_id' => ['nullable', 'string', 'max:100'],
            'payment_gateway' => ['nullable', 'string', 'in:payuni,newebpay'],
            'is_visible' => ['nullable', 'boolean'],
            'course_type' => ['required', 'in:standard,drip'],
            'drip_interval_days' => ['nullable', 'required_if:course_type,drip', 'integer', 'min:1', 'max:30'],
            'drip_days' => ['nullable', 'array'],
            'drip_days.*' => ['integer', 'min:0', 'max:365'],
            'target_course_ids' => ['nullable', 'array'],
            'target_course_ids.*' => ['exists:courses,id'],
        ];
    }

    /**
     * The drip schedule must run strictly forwards (010 FR-037).
     *
     * The sending cursor only ever moves forwards, so `Day 0 -> 7 -> 3`
     * actually behaves as `0 -> 7 -> 7` — the third email waits for the second.
     * Nothing on screen says so, which makes rejecting it at save time cheaper
     * than explaining it afterwards.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $this->validateBundle($validator);
        });

        $validator->after(function ($validator) {
            $days = $this->input('drip_days');

            if ($this->input('course_type') !== 'drip' || ! is_array($days) || $days === []) {
                return;
            }

            $lessons = $this->route('course')->lessons()
                ->orderBy('sort_order')
                ->get(['id']);

            if (array_diff(array_keys($days), $lessons->pluck('id')->map(fn ($id) => (string) $id)->all())) {
                $validator->errors()->add('drip_days', '發信排程包含不屬於本課程的小節');

                return;
            }

            $previous = null;
            foreach ($lessons as $index => $lesson) {
                $day = $days[$lesson->id] ?? null;

                if ($day === null) {
                    continue;
                }

                $day = (int) $day;

                if ($index === 0 && $day !== 0) {
                    $validator->errors()->add('drip_days', '第 1 封信固定為 Day 0（訂閱當下寄出）');

                    return;
                }

                if ($previous !== null && $day <= $previous) {
                    $validator->errors()->add(
                        'drip_days',
                        '第 ' . ($index + 1) . ' 封信的天數必須大於前一封（目前為 Day ' . $day . '，前一封是 Day ' . $previous . '）',
                    );

                    return;
                }

                $previous = $day;
            }
        });
    }

    /**
     * Bundle perk guards (011 US37).
     *
     * Two rules the field-level rules cannot express:
     *
     * - Perks are high-ticket only (FR-200), for the same reason plans are
     *   (D82): no other product type has a plan picker to sell them through.
     * - Clearing the name while members still hold credits is refused (FR-212):
     *   those credits would become an unnamed number nobody can explain.
     *   Renaming stays allowed — that is only display text.
     */
    protected function validateBundle($validator): void
    {
        $course = $this->route('course');
        $posted = ['bundle_name', 'bundle_redeem_points', 'bundle_default_quantity', 'bundle_unlimited'];
        $touched = array_filter($posted, fn ($key) => filled($this->input($key)));

        if ($this->input('type') !== 'high_ticket') {
            foreach ($touched as $key) {
                $validator->errors()->add($key, '只有客製服務（高價課）可以設定附帶福利');
            }

            return;
        }

        if (blank($this->input('bundle_name')) && filled($course?->bundle_name)) {
            $holders = $course->purchases()
                ->paidStatus()
                ->where(fn ($query) => $query->where('bundle_balance', '>', 0)->orWhere('bundle_granted', '>', 0))
                ->count();

            if ($holders > 0) {
                $validator->errors()->add(
                    'bundle_name',
                    "還有 {$holders} 位學員持有此福利次數，請先處理完再停用（改名不受限）",
                );
            }
        }
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
            'price.required_unless' => '請輸入課程價格',
            'price.numeric' => '課程價格必須是數字',
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
            'original_price.integer' => '原價必須是整數',
            'original_price.min' => '原價不能為負數',
            'promo_ends_at.date' => '優惠到期時間格式不正確',
            'course_type.required' => '請選擇課程模式',
            'course_type.in' => '課程模式無效',
            'drip_interval_days.required_if' => '連鎖課程需設定預設間隔天數',
            'drip_interval_days.integer' => '預設間隔天數必須是整數',
            'drip_interval_days.min' => '預設間隔天數至少為 1 天',
            'drip_interval_days.max' => '預設間隔天數不能超過 30 天',
            'drip_days.array' => '發信排程格式無效',
            'drip_days.*.integer' => '發信天數必須是整數',
            'drip_days.*.min' => '發信天數不能為負數',
            'drip_days.*.max' => '發信天數不能超過 365 天',
            'target_course_ids.array' => '目標課程格式無效',
            'target_course_ids.*.exists' => '選擇的目標課程不存在',
            'bundle_name.max' => '福利名稱不可超過 50 個字',
            'bundle_redeem_points.integer' => '每次加購所需積分必須是整數',
            'bundle_redeem_points.min' => '每次加購所需積分至少為 1',
            'bundle_default_quantity.integer' => '預設次數必須是整數',
            'bundle_default_quantity.min' => '預設次數不能為負數',
        ];
    }
}
