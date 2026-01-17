<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class CourseImageController extends Controller
{
    /**
     * Display the image gallery for a course.
     */
    public function index(Course $course): Response
    {
        $images = $course->images()
            ->latest()
            ->get()
            ->map(fn ($image) => [
                'id' => $image->id,
                'filename' => $image->filename,
                'url' => $image->url,
                'created_at' => $image->created_at->format('Y-m-d H:i'),
            ]);

        return Inertia::render('Admin/Courses/Gallery', [
            'course' => [
                'id' => $course->id,
                'name' => $course->name,
            ],
            'images' => $images,
        ]);
    }

    /**
     * Store a newly uploaded image.
     */
    public function store(Request $request, Course $course): RedirectResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:10240'],
        ], [
            'image.required' => '請選擇圖片',
            'image.image' => '檔案必須是圖片',
            'image.mimes' => '圖片格式必須是 jpg, jpeg, png, gif 或 webp',
            'image.max' => '圖片大小不能超過 10MB',
        ]);

        $file = $request->file('image');
        $path = $file->store("course-images/{$course->id}", 'public');

        $course->images()->create([
            'path' => $path,
            'filename' => $file->getClientOriginalName(),
        ]);

        return redirect()
            ->route('admin.images.index', $course)
            ->with('success', '圖片上傳成功');
    }

    /**
     * Remove the specified image.
     */
    public function destroy(CourseImage $image): RedirectResponse
    {
        $courseId = $image->course_id;

        // Delete file from storage
        Storage::disk('public')->delete($image->path);

        $image->delete();

        return redirect()
            ->route('admin.images.index', $courseId)
            ->with('success', '圖片已刪除');
    }
}
