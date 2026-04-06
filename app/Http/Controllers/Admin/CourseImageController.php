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
                'width' => $image->width,
                'height' => $image->height,
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

        // Get image dimensions
        $dimensions = getimagesize($file->getPathname());
        $width = $dimensions[0] ?? null;
        $height = $dimensions[1] ?? null;

        $course->images()->create([
            'path' => $path,
            'filename' => $file->getClientOriginalName(),
            'width' => $width,
            'height' => $height,
        ]);

        return redirect()
            ->route('admin.images.index', $course)
            ->with('success', '圖片上傳成功');
    }

    /**
     * Store multiple uploaded images at once.
     */
    public function batchStore(Request $request, Course $course): RedirectResponse
    {
        $request->validate([
            'images'   => ['required', 'array', 'min:1', 'max:20'],
            'images.*' => ['required', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:10240'],
        ], [
            'images.required'   => '請選擇至少一張圖片',
            'images.max'        => '單次最多上傳 20 張',
            'images.*.image'    => '檔案必須是圖片',
            'images.*.mimes'    => '僅支援 jpg、png、gif、webp',
            'images.*.max'      => '單張圖片不可超過 10MB',
        ]);

        foreach ($request->file('images') as $file) {
            $path = $file->store("course-images/{$course->id}", 'public');
            $dimensions = getimagesize($file->getPathname());

            $course->images()->create([
                'path'     => $path,
                'filename' => $file->getClientOriginalName(),
                'width'    => $dimensions[0] ?? null,
                'height'   => $dimensions[1] ?? null,
            ]);
        }

        return back()->with('success', '圖片上傳成功');
    }

    /**
     * Delete multiple images at once.
     */
    public function batchDestroy(Request $request): RedirectResponse
    {
        $request->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'exists:course_images,id'],
        ]);

        $images = CourseImage::whereIn('id', $request->input('ids'))->get();

        foreach ($images as $image) {
            Storage::disk('public')->delete($image->path);
            $image->delete();
        }

        return back()->with('success', $images->count() . ' 張圖片已刪除');
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
