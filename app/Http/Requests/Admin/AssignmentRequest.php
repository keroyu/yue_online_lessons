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
}
