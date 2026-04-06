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

        $images = CourseImage::with('course')->whereIn('id', $request->input('ids'))->get();
        $count  = $images->count();

        // Capture URL → course pairs before deletion
        $toClean = $images->map(fn ($img) => ['course' => $img->course, 'url' => $img->url]);

        foreach ($images as $image) {
            Storage::disk('public')->delete($image->path);
            $image->delete();
        }

        foreach ($toClean as ['course' => $course, 'url' => $url]) {
            $this->purgeFromDescription($course, $url);
        }

        return back()->with('success', $count . ' 張圖片已刪除');
    }

    /**
     * Remove the specified image.
     */
    public function destroy(CourseImage $image): RedirectResponse
    {
        $course = $image->course;
        $url    = $image->url;

        Storage::disk('public')->delete($image->path);
        $image->delete();

        $this->purgeFromDescription($course, $url);

        return redirect()
            ->route('admin.images.index', $course->id)
            ->with('success', '圖片已刪除');
    }

    /**
     * Remove all references to $url from the course description_md.
     * Handles both <img src="URL"> tags and ![alt](URL) markdown syntax.
     */
    private function purgeFromDescription(Course $course, string $url): void
    {
        if (empty($course->description_md)) {
            return;
        }

        $escaped = preg_quote($url, '/');
        $md = $course->description_md;

        // Remove HTML <img> tags referencing this URL
        $md = preg_replace('/<img\b[^>]*\bsrc="' . $escaped . '"[^>]*\/?>/i', '', $md);

        // Remove Markdown image syntax ![alt](URL)
        $md = preg_replace('/!\[[^\]]*\]\(' . $escaped . '\)/i', '', $md);

        if ($md !== $course->description_md) {
            $course->update(['description_md' => $md]);
        }
    }
}
