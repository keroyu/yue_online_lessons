<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddToCartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = auth()->id();

        return [
            'course_id' => [
                'required',
                'integer',
                'exists:courses,id',
                function ($attr, $value, $fail) use ($userId) {
                    $course = \App\Models\Course::find($value);
                    if (!$course) {
                        return $fail('課程不存在。');
                    }
                    if ($course->portaly_product_id) {
                        return $fail('Portaly 課程不支援購物車。');
                    }
                    if ($course->price <= 0) {
                        return $fail('免費課程請直接報名。');
                    }
                    if ($course->status !== 'selling' || !$course->is_published) {
                        return $fail('課程目前無法購買。');
                    }
                    if ($userId && \App\Models\Purchase::where('user_id', $userId)->where('course_id', $value)->where('status', 'paid')->exists()) {
                        return $fail('您已購買此課程。');
                    }
                },
            ],
        ];
    }
}
