<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\AssignmentCompletion;
use App\Models\HomeworkNotification;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AssignmentService
{
    public function markComplete(User $student, Assignment $assignment): array
    {
        if (AssignmentCompletion::where('assignment_id', $assignment->id)->where('user_id', $student->id)->exists()) {
            return ['success' => false, 'error' => '此學員的作業已標記為完成'];
        }

        $course = $assignment->lesson->course;

        DB::transaction(function () use ($student, $assignment, $course) {
            AssignmentCompletion::create([
                'assignment_id' => $assignment->id,
                'user_id' => $student->id,
            ]);

            $student->increment('points', 100);

            HomeworkNotification::create([
                'user_id' => $student->id,
                'type' => 'completion',
                'course_name' => $course->name,
                'course_id' => $course->id,
                'lesson_id' => $assignment->lesson_id,
                'is_read' => false,
            ]);
        });

        return ['success' => true];
    }
}
