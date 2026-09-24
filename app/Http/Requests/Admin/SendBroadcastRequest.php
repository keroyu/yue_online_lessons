<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\NormalizesTaipeiInput;
use Illuminate\Foundation\Http\FormRequest;

class SendBroadcastRequest extends FormRequest
{
    use NormalizesTaipeiInput;

    public function authorize(): bool
    {
        return true; // Authorization handled by admin middleware
    }

    protected function prepareForValidation(): void
    {
        $this->readAsTaipei('scheduled_at');
    }

    public function rules(): array
    {
        return [
            'post_id' => ['required', 'integer', 'exists:posts,id'],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
        ];
    }

    public function messages(): array
    {
        return [
            'post_id.required' => '請選擇要寄送的文章',
            'post_id.exists' => '選擇的文章不存在',
            'scheduled_at.after' => '排程時間必須在未來',
        ];
    }
}
