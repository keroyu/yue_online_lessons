<?php

namespace App\Http\Requests\Member;

use App\Models\Comment;
use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'max:5000'],
            'parent_id' => [
                'nullable',
                'exists:comments,id',
                function ($attribute, $value, $fail) {
                    if ($value === null) {
                        return;
                    }
                    $parent = Comment::find($value);
                    if (!$parent) {
                        $fail('指定的回覆對象不存在');
                        return;
                    }
                    // Must be top-level (no nesting beyond 2 levels)
                    if ($parent->parent_id !== null) {
                        $fail('不支援超過兩層的巢狀回覆');
                        return;
                    }
                    // Must belong to the same assignment
                    $assignment = $this->route('assignment');
                    if ($parent->assignment_id !== $assignment->id) {
                        $fail('回覆對象不屬於此作業');
                    }
                },
            ],
        ];
    }
}
