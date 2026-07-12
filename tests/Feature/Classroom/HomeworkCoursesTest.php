<?php

namespace Tests\Feature\Classroom;

use App\Models\Assignment;
use App\Models\Chapter;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The 作業批改 course dropdown must list ALL courses (including ones with no question yet,
 * so a first question can be added), newest-assigned course first and empty courses last.
 * 作業題目管理 defaults its own course picker to the most-recently-assigned course.
 */
class HomeworkCoursesTest extends TestCase
{
    use RefreshDatabase;

    private int $seq = 0;

    private function admin(): User
    {
        return User::create(['email' => 'admin@example.com', 'role' => 'admin']);
    }

    private function makeCourse(string $name): Course
    {
        $this->seq++;
        return Course::create([
            'name' => $name, 'slug' => 'c-' . $this->seq, 'tagline' => 't', 'description' => 'd',
            'price' => 1000, 'instructor_name' => 'I', 'type' => 'lecture', 'status' => 'selling',
            'course_type' => 'standard', 'is_published' => true, 'is_visible' => true, 'payment_gateway' => 'payuni',
        ]);
    }

    private function addLesson(Course $course): Lesson
    {
        $chapter = Chapter::create(['course_id' => $course->id, 'title' => 'Ch', 'sort_order' => 1]);
        return Lesson::create(['course_id' => $course->id, 'chapter_id' => $chapter->id, 'title' => 'L']);
    }

    private function courseWithAssignment(string $name, Carbon $assignedAt): Course
    {
        $course = $this->makeCourse($name);
        $lesson = $this->addLesson($course);
        $a = Assignment::create(['lesson_id' => $lesson->id, 'md_content' => 'hw', 'is_published' => true]);
        DB::table('assignments')->where('id', $a->id)->update(['created_at' => $assignedAt]);
        return $course;
    }

    public function test_dropdown_lists_all_courses_newest_assignment_first_empty_last(): void
    {
        $admin = $this->admin();

        $this->courseWithAssignment('OldCourse', now()->subDays(3));
        $new = $this->courseWithAssignment('NewCourse', now()->subDay());

        // A course with a lesson but no assignment must STILL appear (so a first question can be added).
        $this->addLesson($this->makeCourse('EmptyCourse'));

        $this->actingAs($admin)
            ->get('/admin/homework')
            ->assertOk()
            ->assertInertia(fn ($p) => $p
                ->component('Admin/Homework/Index')
                ->has('courses', 3)
                ->where('courses.0.name', 'NewCourse')   // latest assignment first
                ->where('courses.1.name', 'OldCourse')
                ->where('courses.2.name', 'EmptyCourse') // no-question course sinks to bottom
                ->where('filters.manage_course_id', $new->id)); // 作業題目管理 defaults to newest-assigned course
    }

    public function test_latest_assignment_in_a_course_lifts_it_to_the_top(): void
    {
        $admin = $this->admin();

        // "A" starts oldest; then a brand-new assignment is added to A → A should jump to top.
        $a = $this->courseWithAssignment('CourseA', now()->subDays(5));
        $this->courseWithAssignment('CourseB', now()->subDays(2));

        $lesson = $this->addLesson($a);
        $fresh = Assignment::create(['lesson_id' => $lesson->id, 'md_content' => 'hw2', 'is_published' => true]);
        DB::table('assignments')->where('id', $fresh->id)->update(['created_at' => now()]);

        $this->actingAs($admin)
            ->get('/admin/homework')
            ->assertOk()
            ->assertInertia(fn ($p) => $p
                ->has('courses', 2)
                ->where('courses.0.name', 'CourseA')   // its newest assignment now leads
                ->where('courses.1.name', 'CourseB'));
    }
}
