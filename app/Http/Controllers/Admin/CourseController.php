<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCourseRequest;
use App\Http\Requests\Admin\UpdateCourseRequest;
use App\Models\Course;
use App\Models\DripConversionTarget;
use App\Models\Purchase;
use App\Models\SiteSetting;
use App\Services\CouponChainService;
use App\Services\DripService;
use App\Services\TrafficSourceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\Order;
use App\Models\OrderItem;
use Inertia\Inertia;
use Inertia\Response;

class CourseController extends Controller
{
    public function __construct(
        protected DripService $dripService,
        protected CouponChainService $couponChainService,
        protected TrafficSourceService $trafficSource,
    ) {}
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
                'portaly_product_id' => $course->portaly_product_id,
                'content_category' => $course->content_category,
                'product_type' => $course->type,
            ]);

        return Inertia::render('Admin/Courses/Index', [
            'courses' => $courses,
            'contentCategories' => HomepageSettingController::contentCategories(),
        ]);
    }

    /**
     * Show the form for creating a new course.
     */
    public function create(): Response
    {
        return Inertia::render('Admin/Courses/Create', [
            'gatewayConfigured' => $this->gatewayConfigured(),
            'contentCategories' => \App\Http\Controllers\Admin\HomepageSettingController::contentCategories(),
            'availableCourses' => $this->availableTargetCourses(),
            'couponChains' => $this->couponChainService->editorOptions(),
        ]);
    }

    /**
     * Courses selectable as drip conversion targets, shared by create() and edit().
     * A drip course cannot be its own funnel target, nor can another drip course.
     */
    private function availableTargetCourses(?Course $exclude = null)
    {
        return Course::when($exclude, fn ($query) => $query->where('id', '!=', $exclude->id))
            ->where('course_type', '!=', 'drip')
            ->published()
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * Store a newly created course in storage.
     */
    public function store(StoreCourseRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Extract target_course_ids before saving (not a course column)
        $targetCourseIds = $data['target_course_ids'] ?? null;
        unset($data['target_course_ids']);

        // Handle thumbnail upload
        if ($request->hasFile('thumbnail')) {
            $data['thumbnail'] = $request->file('thumbnail')->store('thumbnails', 'public');
        }

        // Clear drip fields on a standard course
        if (($data['course_type'] ?? 'standard') === 'standard') {
            $data['drip_interval_days'] = null;
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
        $course = DB::transaction(function () use ($data, $targetCourseIds) {
            $course = Course::create($data);

            // Store conversion targets for drip courses
            if (($data['course_type'] ?? 'standard') === 'drip' && $targetCourseIds) {
                foreach ($targetCourseIds as $targetId) {
                    DripConversionTarget::create([
                        'drip_course_id' => $course->id,
                        'target_course_id' => $targetId,
                    ]);
                }
            }

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
        $availableCourses = $this->availableTargetCourses($course);

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
                'slug' => $course->slug,
                'tagline' => $course->tagline,
                'meta_description' => $course->meta_description,
                'description' => $course->description,
                'description_md' => $course->description_md,
                'free_success_md' => $course->free_success_md,
                'promo_html' => $course->promo_html,
                'promo_delay_seconds' => $course->promo_delay_seconds,
                'price' => $course->price,
                'original_price' => $course->original_price,
                'promo_ends_at' => $course->promo_ends_at?->format('Y-m-d\TH:i'),
                'is_promo_active' => $course->is_promo_active,
                'thumbnail' => $course->thumbnail,
                'instructor_name' => $course->instructor_name,
                'product_type' => $course->type,
                'content_category' => $course->content_category,
                'status' => $course->status,
                'is_published' => $course->is_published,
                'sale_at' => $course->sale_at?->format('Y-m-d\TH:i'),
                'duration_minutes' => $course->duration_minutes,
                'duration_formatted' => $course->duration_formatted,
                'portaly_url' => $course->portaly_url,
                'portaly_product_id' => $course->portaly_product_id,
                'is_visible' => $course->is_visible,
                'delivery_mode' => $course->course_type ?? 'standard',
                'redeem_points' => $course->redeem_points,
                'drip_interval_days' => $course->drip_interval_days,
                'target_course_ids' => $targetCourseIds,
                'high_ticket_hide_price' => $course->high_ticket_hide_price,
                'is_high_ticket' => $course->is_high_ticket,
                'payment_gateway' => $course->payment_gateway,
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
            'gatewayConfigured' => $this->gatewayConfigured(),
            'contentCategories' => \App\Http\Controllers\Admin\HomepageSettingController::contentCategories(),
            'couponChains' => $this->couponChainService->editorOptions(),
        ]);
    }

    private function gatewayConfigured(): array
    {
        return [
            'payuni'   => !empty(SiteSetting::get('payuni_merchant_id',   config('services.payuni.merchant_id')))
                       && !empty(SiteSetting::get('payuni_hash_key',      config('services.payuni.hash_key')))
                       && !empty(SiteSetting::get('payuni_hash_iv',       config('services.payuni.hash_iv'))),
            'newebpay' => !empty(SiteSetting::get('newebpay_merchant_id', config('services.newebpay.merchant_id')))
                       && !empty(SiteSetting::get('newebpay_hash_key',    config('services.newebpay.hash_key')))
                       && !empty(SiteSetting::get('newebpay_hash_iv',     config('services.newebpay.hash_iv'))),
        ];
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
        if ($course->purchases()->purchaseType()->paidStatus()->exists()) {
            return redirect()
                ->route('admin.courses.index')
                ->with('error', '此課程已有學員購買，無法刪除');
        }

        // Delete course and system-assigned purchases in a transaction
        DB::transaction(function () use ($course) {
            // Delete system-assigned purchases for this course
            $course->purchases()->systemAssignedType()->delete();

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

    public function traffic(Course $course, Request $request): Response
    {
        $days = $request->input('days');
        $days = in_array((int) $days, [7, 30, 90], true) ? (int) $days : null;

        $query = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('order_items.course_id', $course->id)
            ->where('orders.status', 'paid')
            ->when($days, fn ($q) => $q->where('orders.created_at', '>=', now()->subDays($days)));

        $totalOrders = (clone $query)->distinct()->count('orders.id');

        $trackedOrders = (clone $query)
            ->where(function ($q) {
                $q->whereNotNull('orders.utm_source')
                  ->orWhereNotNull('orders.referrer_domain')
                  ->orWhereNotNull('orders.gclid')
                  ->orWhereNotNull('orders.fbclid')
                  ->orWhereNotNull('orders.ttclid');
            })
            ->distinct()
            ->count('orders.id');

        $sources = (clone $query)
            ->select(
                'orders.utm_source', 'orders.utm_medium', 'orders.utm_campaign',
                'orders.utm_term', 'orders.utm_content', 'orders.referrer_domain',
                'orders.gclid', 'orders.fbclid', 'orders.ttclid',
                DB::raw('COUNT(DISTINCT orders.id) as order_count'),
                DB::raw('SUM(order_items.unit_price) as revenue')
            )
            ->groupBy(
                'orders.utm_source', 'orders.utm_medium', 'orders.utm_campaign',
                'orders.utm_term', 'orders.utm_content', 'orders.referrer_domain',
                'orders.gclid', 'orders.fbclid', 'orders.ttclid', 'orders.first_touch'
            )
            ->get()
            ->map(function ($row) {
                $hasClickId = $row->gclid || $row->fbclid || $row->ttclid;
                if ($hasClickId) {
                    // fbclid rides on organic Meta clicks too, so the platform is
                    // resolved by the shared rule instead of being assumed to be
                    // Facebook (002 FR-024).
                    $displaySource = $row->utm_source ?: $this->trafficSource->resolveSource([
                        'gclid'           => $row->gclid,
                        'fbclid'          => $row->fbclid,
                        'ttclid'          => $row->ttclid,
                        'referrer_domain' => $row->referrer_domain,
                    ])['source'];
                } elseif ($row->utm_source) {
                    $displaySource = $row->utm_source;
                } elseif ($row->referrer_domain) {
                    $displaySource = "(外部連結) {$row->referrer_domain}";
                } else {
                    $displaySource = '(直接造訪)';
                }

                return [
                    'utm_source'       => $row->utm_source,
                    'utm_medium'       => $row->utm_medium,
                    'utm_campaign'     => $row->utm_campaign,
                    'utm_term'         => $row->utm_term,
                    'utm_content'      => $row->utm_content,
                    'referrer_domain'  => $row->referrer_domain,
                    'gclid'            => $row->gclid,
                    'fbclid'           => $row->fbclid,
                    'ttclid'           => $row->ttclid,
                    'display_source'   => $displaySource,
                    'order_count'      => (int) $row->order_count,
                    'revenue'          => (float) $row->revenue,
                ];
            })
            ->values()
            ->all();

        return Inertia::render('Admin/Courses/Traffic', [
            'course'  => ['id' => $course->id, 'name' => $course->name, 'url' => route('course.show', $course)],
            'filters' => ['days' => $days],
            'traffic' => [
                'total_orders'   => $totalOrders,
                'tracked_orders' => $trackedOrders,
                'sources'        => $sources,
            ],
        ]);
    }

    public function trafficExport(Course $course, Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $days = $request->input('days');
        $days = in_array((int) $days, [7, 30, 90], true) ? (int) $days : null;

        $query = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('users', 'users.id', '=', 'orders.user_id')
            ->where('order_items.course_id', $course->id)
            ->where('orders.status', 'paid')
            ->when($days, fn ($q) => $q->where('orders.created_at', '>=', now()->subDays($days)))
            ->select(
                'orders.merchant_order_no', 'orders.created_at', 'orders.buyer_email',
                'order_items.unit_price',
                'orders.utm_source', 'orders.utm_medium', 'orders.utm_campaign',
                'orders.utm_term', 'orders.utm_content', 'orders.referrer_domain',
                'orders.gclid', 'orders.fbclid', 'orders.ttclid', 'orders.first_touch'
            )
            ->orderByDesc('orders.created_at');

        $filename = 'course-' . $course->id . '-traffic-' . now()->format('Ymd') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, [
                '訂單編號', '購買時間', '購買者 Email', '金額',
                'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content',
                'referrer_domain', 'gclid', 'fbclid', 'ttclid', 'first_touch',
            ]);
            $query->chunk(200, function ($rows) use ($handle) {
                foreach ($rows as $row) {
                    fputcsv($handle, [
                        $row->merchant_order_no ?? '',
                        $row->created_at,
                        $row->buyer_email,
                        $row->unit_price,
                        $row->utm_source ?? '',
                        $row->utm_medium ?? '',
                        $row->utm_campaign ?? '',
                        $row->utm_term ?? '',
                        $row->utm_content ?? '',
                        $row->referrer_domain ?? '',
                        $row->gclid ?? '',
                        $row->fbclid ?? '',
                        $row->ttclid ?? '',
                        $row->first_touch ?? '',
                    ]);
                }
            });
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
