<?php

namespace App\Http\Requests\Admin;

use App\Models\Lesson;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * The plan-side counterpart of SyncLessonPlansRequest: one plan, the complete
 * set of lessons it covers. Used by the chapter shortcuts, which need to move
 * a dozen lessons at once.
 */
class SyncPlanLessonsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            // Present-but-empty means "this plan covers nothing", which is a
            // legal (if unusual) state — the same as unticking every chip.
            'lesson_ids' => ['present', 'array'],
            'lesson_ids.*' => ['integer', 'exists:lessons,id'],
        ];
    }

    /** Every lesson must belong to the plan's own course. */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $lessonIds = $this->input('lesson_ids', []);

            if (empty($lessonIds)) {
                return;
            }

            $foreign = Lesson::whereIn('id', $lessonIds)
                ->where('course_id', '!=', $this->route('plan')->course_id)
                ->exists();

            if ($foreign) {
                $validator->errors()->add('lesson_ids', '小節不屬於此課程');
            }
        });
    }
}
