<?php

namespace App\Http\Requests\Admin;

use App\Models\CoursePlan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SyncLessonPlansRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            // An empty array is meaningful: it unassigns the lesson from every
            // plan, which makes it invisible to plan holders (011 D81).
            'plan_ids' => ['present', 'array'],
            'plan_ids.*' => ['integer', 'exists:course_plans,id'],
        ];
    }

    /**
     * Every plan must belong to this lesson's own course — otherwise one course
     * could hand out another course's content.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $planIds = $this->input('plan_ids', []);

            if (empty($planIds)) {
                return;
            }

            $foreign = CoursePlan::whereIn('id', $planIds)
                ->where('course_id', '!=', $this->route('lesson')->course_id)
                ->exists();

            if ($foreign) {
                $validator->errors()->add('plan_ids', '方案不屬於此課程');
            }
        });
    }
}
