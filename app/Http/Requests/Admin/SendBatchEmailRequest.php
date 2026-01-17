<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SendBatchEmailRequest extends FormRequest
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
            'subject' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:10000'],
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
            'subject.required' => '郵件主旨為必填',
            'subject.max' => '郵件主旨不能超過 200 字',
            'body.required' => '郵件內容為必填',
            'body.max' => '郵件內容不能超過 10000 字',
        ];
    }
}
