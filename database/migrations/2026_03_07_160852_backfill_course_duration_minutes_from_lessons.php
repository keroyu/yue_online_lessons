<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Recalculate duration_minutes for all courses from their video lessons.
     */
    public function up(): void
    {
        $courses = DB::table('courses')->whereNull('deleted_at')->get(['id']);

        foreach ($courses as $course) {
            $totalSeconds = DB::table('lessons')
                ->where('course_id', $course->id)
                ->whereNotNull('video_id')
                ->sum('duration_seconds');

            DB::table('courses')
                ->where('id', $course->id)
                ->update(['duration_minutes' => (int) round($totalSeconds / 60)]);
        }
    }

    /**
     * There is no meaningful rollback for this data migration.
     */
    public function down(): void
    {
        //
    }
};
