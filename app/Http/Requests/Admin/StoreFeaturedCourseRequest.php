<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreFeaturedCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'blurb'     => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'course_id.required' => '請選擇課程',
            'course_id.exists'   => '選擇的課程不存在',
            'blurb.max'          => '介紹文字不可超過 500 字',
        ];
    }
}
