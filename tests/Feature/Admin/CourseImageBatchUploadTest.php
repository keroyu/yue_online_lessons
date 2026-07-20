<?php

namespace Tests\Feature\Admin;

use App\Models\Course;
use App\Models\CourseImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CourseImageBatchUploadTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create(['email' => 'admin@example.com', 'role' => 'admin']);
    }

    private function course(): Course
    {
        return Course::create([
            'name'            => 'Course 1',
            'slug'            => 'c1',
            'tagline'         => 'tag',
            'description'     => 'desc',
            'price'           => 1000,
            'instructor_name' => 'Tester',
            'type'            => 'lecture',
            'status'          => 'selling',
            'course_type'     => 'standard',
            'is_published'    => true,
            'is_visible'      => true,
            'payment_gateway' => 'payuni',
        ]);
    }

    public function test_single_file_per_request_creates_one_image(): void
    {
        Storage::fake('public');
        $course = $this->course();

        $this->actingAs($this->admin())
            ->post(route('admin.images.batch-store', $course), [
                'images' => [UploadedFile::fake()->image('a.jpg', 100, 100)],
            ])
            ->assertRedirect();

        $this->assertSame(1, $course->images()->count());
        Storage::disk('public')->assertExists($course->images()->first()->path);
    }

    public function test_multiple_files_in_one_request_all_persist(): void
    {
        Storage::fake('public');
        $course = $this->course();

        $files = [
            UploadedFile::fake()->image('a.jpg', 100, 100),
            UploadedFile::fake()->image('b.png', 120, 80),
            UploadedFile::fake()->image('c.webp', 90, 90),
        ];

        $this->actingAs($this->admin())
            ->post(route('admin.images.batch-store', $course), ['images' => $files])
            ->assertRedirect();

        $this->assertSame(3, $course->images()->count());
        $this->assertSame(3, CourseImage::count());
    }

    public function test_non_image_is_rejected(): void
    {
        Storage::fake('public');
        $course = $this->course();

        $this->actingAs($this->admin())
            ->post(route('admin.images.batch-store', $course), [
                'images' => [UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf')],
            ])
            ->assertSessionHasErrors('images.0');

        $this->assertSame(0, $course->images()->count());
    }
}
