<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Admin middleware handles authorization.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'user_id'   => 'required|exists:users,id',
            'course_id' => 'required|exists:courses,id',
            'type'      => 'required|in:system_assigned,gift',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'user_id.required'   => '請選擇會員',
            'user_id.exists'     => '會員不存在',
            'course_id.required' => '請選擇課程',
            'course_id.exists'   => '課程不存在',
            'type.required'      => '請選擇交易類型',
            'type.in'            => '交易類型無效，請選擇 system_assigned 或 gift',
        ];
    }
}
