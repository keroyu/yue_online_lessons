<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class GiftCourseRequest extends FormRequest
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
            'member_ids' => ['required', 'array', 'min:1'],
            'member_ids.*' => ['exists:users,id'],
            'course_id' => ['required', 'exists:courses,id'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'member_ids.required' => '請選擇至少一位會員',
            'member_ids.array' => '會員 ID 格式不正確',
            'member_ids.min' => '請選擇至少一位會員',
            'member_ids.*.exists' => '選擇的會員不存在',
            'course_id.required' => '請選擇要贈送的課程',
            'course_id.exists' => '選擇的課程不存在',
        ];
    }
}
