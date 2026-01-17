<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCourseRequest;
use App\Http\Requests\Admin\UpdateCourseRequest;
use App\Models\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class CourseController extends Controller
{
    /**
     * Display a listing of courses.
     */
    public function index(): Response
    {
        $courses = Course::withTrashed()
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($course) => [
                'id' => $course->id,
                'name' => $course->name,
                'instructor_name' => $course->instructor_name,
                'status' => $course->status,
                'is_published' => $course->is_published,
                'price' => $course->price,
                'thumbnail' => $course->thumbnail,
                'sale_at' => $course->sale_at?->format('Y-m-d H:i'),
                'deleted_at' => $course->deleted_at,
                'duration_formatted' => $course->duration_formatted,
            ]);

        return Inertia::render('Admin/Courses/Index', [
            'courses' => $courses,
        ]);
    }

    /**
     * Show the form for creating a new course.
     */
    public function create(): Response
    {
        return Inertia::render('Admin/Courses/Create');
    }

    /**
     * Store a newly created course in storage.
     */
    public function store(StoreCourseRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Handle thumbnail upload
        if ($request->hasFile('thumbnail')) {
            $data['thumbnail'] = $request->file('thumbnail')->store('thumbnails', 'public');
        }

        // Set default values
        $data['status'] = 'draft';
        $data['is_published'] = false;
        $data['sort_order'] = Course::max('sort_order') + 1;

        Course::create($data);

        return redirect()
            ->route('admin.courses.index')
            ->with('success', '課程建立成功');
    }

    /**
     * Show the form for editing the specified course.
     */
    public function edit(Course $course): Response
    {
        return Inertia::render('Admin/Courses/Edit', [
            'course' => [
                'id' => $course->id,
                'name' => $course->name,
                'tagline' => $course->tagline,
                'description' => $course->description,
                'description_html' => $course->description_html,
                'price' => $course->price,
                'thumbnail' => $course->thumbnail,
                'instructor_name' => $course->instructor_name,
                'type' => $course->type,
                'status' => $course->status,
                'is_published' => $course->is_published,
                'sale_at' => $course->sale_at?->format('Y-m-d\TH:i'),
                'duration_minutes' => $course->duration_minutes,
                'duration_formatted' => $course->duration_formatted,
                'portaly_url' => $course->portaly_url,
                'portaly_product_id' => $course->portaly_product_id,
            ],
        ]);
    }

    /**
     * Update the specified course in storage.
     */
    public function update(UpdateCourseRequest $request, Course $course): RedirectResponse
    {
        $data = $request->validated();

        // Handle thumbnail upload
        if ($request->hasFile('thumbnail')) {
            // Delete old thumbnail if exists
            if ($course->thumbnail) {
                Storage::disk('public')->delete($course->thumbnail);
            }
            $data['thumbnail'] = $request->file('thumbnail')->store('thumbnails', 'public');
        }

        $course->update($data);

        return redirect()
            ->route('admin.courses.edit', $course)
            ->with('success', '課程更新成功');
    }

    /**
     * Remove the specified course from storage (soft delete).
     */
    public function destroy(Course $course): RedirectResponse
    {
        // Check if course has any purchases
        if ($course->purchases()->exists()) {
            return redirect()
                ->route('admin.courses.index')
                ->with('error', '此課程已有學員購買，無法刪除');
        }

        $course->delete();

        return redirect()
            ->route('admin.courses.index')
            ->with('success', '課程已刪除');
    }

    /**
     * Publish the course (auto-determine preorder/selling based on sale_at).
     */
    public function publish(Course $course): RedirectResponse
    {
        // Determine status based on sale_at
        if ($course->sale_at && $course->sale_at->isFuture()) {
            $course->status = 'preorder';
        } else {
            $course->status = 'selling';
            // Clear sale_at if it's in the past or not set
            $course->sale_at = null;
        }

        $course->is_published = true;
        $course->save();

        $statusText = $course->status === 'preorder' ? '預購中' : '熱賣中';

        return redirect()
            ->route('admin.courses.edit', $course)
            ->with('success', "課程已發佈為「{$statusText}」");
    }

    /**
     * Unpublish the course (set status back to draft).
     */
    public function unpublish(Course $course): RedirectResponse
    {
        $course->status = 'draft';
        $course->is_published = false;
        $course->save();

        return redirect()
            ->route('admin.courses.edit', $course)
            ->with('success', '課程已下架為草稿');
    }
}
