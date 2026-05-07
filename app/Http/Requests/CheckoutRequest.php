<?php

namespace App\Http\Requests;

use App\Models\Course;
use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'buyer'        => ['required', 'array'],
            'buyer.name'   => ['required', 'string', 'max:100'],
            'buyer.email'  => ['required', 'email', 'max:255'],
            'buyer.phone'  => ['required', 'string', 'max:20'],
            'agree_terms'  => ['required', 'accepted'],
            'course_ids'   => ['required', 'array', 'min:1'],
            'course_ids.*' => [
                'required',
                'integer',
                'exists:courses,id',
                function ($attr, $value, $fail) {
                    $course = Course::find($value);
                    if (!$course) return $fail('課程不存在。');
                    if ($course->portaly_product_id) return $fail('Portaly 課程不支援購物車結帳。');
                    if ($course->price <= 0) return $fail('免費課程不需結帳。');
                    if ($course->status !== 'selling' || !$course->is_published) return $fail('課程目前無法購買。');
                },
            ],
        ];
    }
}
