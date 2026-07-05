<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\AssignmentCompletion;
use App\Models\HomeworkNotification;
use App\Models\SiteSetting;
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

        $rewardPoints = (int) SiteSetting::get('homework_reward_points', 100);

        DB::transaction(function () use ($student, $assignment, $course, $rewardPoints) {
            AssignmentCompletion::create([
                'assignment_id' => $assignment->id,
                'user_id' => $student->id,
            ]);

            // 積分一律經帳本發放（PointService 為 users.points 唯一寫入點）
            app(PointService::class)->award(
                $student,
                $rewardPoints,
                'earn_homework',
                'assignment',
                $assignment->id,
            );

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
