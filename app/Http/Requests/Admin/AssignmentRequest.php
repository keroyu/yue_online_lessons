<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'question_md' => ['required', 'string', 'max:50000'],
            // Optional context for the AI grading draft only — never rendered to students (FR-016).
            'handout_md'  => ['nullable', 'string', 'max:50000'],
        ];
    }

    /**
     * The page shows these verbatim, so they are UI copy, not developer strings.
     */
    public function messages(): array
    {
        return [
            'question_md.required' => '請填寫作業題目（只填講義無法建立題目）',
            'question_md.max'      => '作業題目最多 50000 字',
            'handout_md.max'       => '講義最多 50000 字',
        ];
    }
}
