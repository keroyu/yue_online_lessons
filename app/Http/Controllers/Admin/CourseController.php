<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCourseRequest;
use App\Http\Requests\Admin\UpdateCourseRequest;
use App\Models\Course;
use App\Models\DripConversionTarget;
use App\Models\DripSubscription;
use App\Models\Purchase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
                'is_visible' => $course->is_visible,
                'price' => $course->price,
                'original_price' => $course->original_price,
                'promo_ends_at' => $course->promo_ends_at?->format('Y-m-d H:i'),
                'is_promo_active' => $course->is_promo_active,
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
        $data['is_visible'] = $data['is_visible'] ?? true;
        $data['sort_order'] = Course::max('sort_order') + 1;

        // Set default promo_ends_at to 30 days from now if original_price is provided
        if (!empty($data['original_price']) && empty($data['promo_ends_at'])) {
            $data['promo_ends_at'] = now()->addDays(30);
        }

        // Create course and auto-assign ownership to admin within a transaction
        $course = DB::transaction(function () use ($data) {
            $course = Course::create($data);

            // Auto-assign course ownership to the creating admin
            Purchase::create([
                'user_id' => auth()->id(),
                'course_id' => $course->id,
                'portaly_order_id' => 'SYSTEM-' . Str::uuid(),
                'amount' => 0,
                'currency' => 'TWD',
                'status' => 'paid',
                'type' => 'system_assigned',
            ]);

            return $course;
        });

        return redirect()
            ->route('admin.courses.index')
            ->with('success', '課程建立成功');
    }

    /**
     * Show the form for editing the specified course.
     */
    public function edit(Course $course): Response
    {
        // Get available courses for conversion target selection (exclude self)
        $availableCourses = Course::where('id', '!=', $course->id)
            ->where('course_type', '!=', 'drip')
            ->published()
            ->orderBy('name')
            ->get(['id', 'name']);

        // Get current target course IDs
        $targetCourseIds = $course->dripConversionTargets()
            ->pluck('target_course_id')
            ->toArray();

        // Get lessons for schedule preview
        $courseLessons = $course->lessons()
            ->orderBy('sort_order')
            ->get(['id', 'title', 'sort_order']);

        return Inertia::render('Admin/Courses/Edit', [
            'course' => [
                'id' => $course->id,
                'name' => $course->name,
                'tagline' => $course->tagline,
                'description' => $course->description,
                'description_html' => $course->description_html,
                'price' => $course->price,
                'original_price' => $course->original_price,
                'promo_ends_at' => $course->promo_ends_at?->format('Y-m-d\TH:i'),
                'is_promo_active' => $course->is_promo_active,
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
                'is_visible' => $course->is_visible,
                'course_type' => $course->course_type ?? 'standard',
                'drip_interval_days' => $course->drip_interval_days,
                'target_course_ids' => $targetCourseIds,
            ],
            'images' => $course->images()
                ->latest()
                ->get()
                ->map(fn ($image) => [
                    'id' => $image->id,
                    'filename' => $image->filename,
                    'url' => $image->url,
                    'width' => $image->width,
                    'height' => $image->height,
                ]),
            'availableCourses' => $availableCourses,
            'courseLessons' => $courseLessons,
        ]);
    }

    /**
     * Update the specified course in storage.
     */
    public function update(UpdateCourseRequest $request, Course $course): RedirectResponse
    {
        $data = $request->validated();

        // Extract target_course_ids before saving (not a course column)
        $targetCourseIds = $data['target_course_ids'] ?? null;
        unset($data['target_course_ids']);

        // Handle thumbnail upload
        if ($request->hasFile('thumbnail')) {
            // Delete old thumbnail if exists
            if ($course->thumbnail) {
                Storage::disk('public')->delete($course->thumbnail);
            }
            $data['thumbnail'] = $request->file('thumbnail')->store('thumbnails', 'public');
        } else {
            // Don't overwrite existing thumbnail if no new file uploaded
            unset($data['thumbnail']);
        }

        // Clear drip fields if switching to standard
        if (($data['course_type'] ?? 'standard') === 'standard') {
            $data['drip_interval_days'] = null;
        }

        DB::transaction(function () use ($course, $data, $targetCourseIds) {
            $course->update($data);

            // Sync conversion targets for drip courses
            if (($data['course_type'] ?? 'standard') === 'drip' && $targetCourseIds !== null) {
                // Delete existing targets
                $course->dripConversionTargets()->delete();

                // Create new targets
                foreach ($targetCourseIds as $targetId) {
                    DripConversionTarget::create([
                        'drip_course_id' => $course->id,
                        'target_course_id' => $targetId,
                    ]);
                }
            } elseif (($data['course_type'] ?? 'standard') === 'standard') {
                // Remove conversion targets when switching to standard
                $course->dripConversionTargets()->delete();
            }
        });

        return redirect()
            ->route('admin.courses.edit', $course)
            ->with('success', '課程更新成功');
    }

    /**
     * Remove the specified course from storage (soft delete).
     */
    public function destroy(Course $course): RedirectResponse
    {
        // Check if course has any paid purchases (exclude system_assigned and gift)
        if ($course->purchases()->paid()->exists()) {
            return redirect()
                ->route('admin.courses.index')
                ->with('error', '此課程已有學員購買，無法刪除');
        }

        // Delete course and system-assigned purchases in a transaction
        DB::transaction(function () use ($course) {
            // Delete system-assigned purchases for this course
            $course->purchases()->systemAssigned()->delete();

            // Soft delete the course
            $course->delete();
        });

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

    /**
     * Display subscribers list for a drip course.
     */
    public function subscribers(Request $request, Course $course): Response
    {
        $statusFilter = $request->input('status');

        $query = DripSubscription::where('course_id', $course->id)
            ->with('user:id,email,nickname');

        if ($statusFilter && in_array($statusFilter, ['active', 'converted', 'completed', 'unsubscribed'])) {
            $query->where('status', $statusFilter);
        }

        $subscribers = $query->orderByDesc('subscribed_at')
            ->paginate(20)
            ->withQueryString();

        // Stats aggregation
        $stats = DripSubscription::where('course_id', $course->id)
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_count,
                SUM(CASE WHEN status = 'converted' THEN 1 ELSE 0 END) as converted_count,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count,
                SUM(CASE WHEN status = 'unsubscribed' THEN 1 ELSE 0 END) as unsubscribed_count
            ")
            ->first();

        $totalLessons = $course->lessons()->count();

        return Inertia::render('Admin/Courses/Subscribers', [
            'course' => [
                'id' => $course->id,
                'name' => $course->name,
                'total_lessons' => $totalLessons,
            ],
            'subscribers' => $subscribers,
            'stats' => [
                'total' => (int) $stats->total,
                'active' => (int) $stats->active_count,
                'converted' => (int) $stats->converted_count,
                'completed' => (int) $stats->completed_count,
                'unsubscribed' => (int) $stats->unsubscribed_count,
            ],
            'filters' => [
                'status' => $statusFilter,
            ],
        ]);
    }
}
