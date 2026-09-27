<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ConsumeBundleCreditRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            // Ids only: whether these members own the course and have credits
            // left is re-read server side, not trusted from here (011 FR-211).
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_ids.required' => '請先勾選學員',
            'user_ids.min' => '請先勾選學員',
        ];
    }
}
