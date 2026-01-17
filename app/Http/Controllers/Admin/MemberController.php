<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SendBatchEmailRequest;
use App\Http\Requests\Admin\UpdateMemberRequest;
use App\Jobs\SendBatchEmailJob;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MemberController extends Controller
{
    /**
     * Display a listing of members with pagination, search, and filters.
     */
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $courseId = $request->input('course_id');
        $sortField = $request->input('sort', 'created_at');
        $sortDirection = $request->input('direction', 'desc');
        $perPage = min($request->input('per_page', 50), 100);

        // Validate sort field
        $allowedSortFields = ['email', 'real_name', 'created_at', 'last_login_at'];
        if (!in_array($sortField, $allowedSortFields)) {
            $sortField = 'created_at';
        }

        // Validate sort direction
        if (!in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'desc';
        }

        // Build query for members only
        $query = User::query()
            ->where('role', 'member');

        // Apply search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                  ->orWhere('real_name', 'like', "%{$search}%")
                  ->orWhere('nickname', 'like', "%{$search}%");
            });
        }

        // Apply course ownership filter
        if ($courseId) {
            $query->whereHas('purchases', function ($q) use ($courseId) {
                $q->where('course_id', $courseId)
                  ->where('status', 'completed');
            });
        }

        // Get matching count before pagination
        $matchingCount = $query->count();

        // Apply sorting and pagination
        $members = $query->orderBy($sortField, $sortDirection)
            ->paginate($perPage)
            ->withQueryString();

        // Get all courses for the filter dropdown
        $courses = Course::select('id', 'name')
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/Members/Index', [
            'members' => $members,
            'filters' => [
                'search' => $search,
                'course_id' => $courseId,
                'sort' => $sortField,
                'direction' => $sortDirection,
            ],
            'courses' => $courses,
            'selectedIds' => $request->input('selected', []),
            'matchingCount' => $matchingCount,
        ]);
    }

    /**
     * Display the specified member's details with course progress.
     */
    public function show(User $member): JsonResponse
    {
        // Ensure we're only showing members (not admins/editors)
        if ($member->role !== 'member') {
            abort(404, '找不到該會員');
        }

        // Load courses with progress calculation
        $courses = $member->purchases()
            ->with(['course.lessons'])
            ->where('status', 'completed')
            ->get()
            ->map(function ($purchase) use ($member) {
                $course = $purchase->course;
                $totalLessons = $course->lessons->count();
                $completedLessons = $member->lessonProgress()
                    ->whereIn('lesson_id', $course->lessons->pluck('id'))
                    ->count();

                return [
                    'id' => $course->id,
                    'name' => $course->name,
                    'purchased_at' => $purchase->created_at->toIso8601String(),
                    'total_lessons' => $totalLessons,
                    'completed_lessons' => $completedLessons,
                    'progress_percent' => $totalLessons > 0
                        ? (int) round($completedLessons / $totalLessons * 100)
                        : 0,
                ];
            });

        return response()->json([
            'member' => [
                'id' => $member->id,
                'email' => $member->email,
                'nickname' => $member->nickname,
                'real_name' => $member->real_name,
                'phone' => $member->phone,
                'birth_date' => $member->birth_date?->format('Y-m-d'),
                'last_login_ip' => $member->last_login_ip,
                'last_login_at' => $member->last_login_at?->toIso8601String(),
                'created_at' => $member->created_at->toIso8601String(),
            ],
            'courses' => $courses,
        ]);
    }

    /**
     * Update the specified member's information.
     */
    public function update(UpdateMemberRequest $request, User $member)
    {
        // Ensure we're only updating members (not admins/editors)
        if ($member->role !== 'member') {
            abort(403, '只能編輯會員資料');
        }

        $member->update($request->validated());

        return back()->with('success', '會員資料更新成功');
    }

    /**
     * Send batch email to selected members.
     */
    public function sendBatchEmail(SendBatchEmailRequest $request): JsonResponse
    {
        $memberIds = $request->input('member_ids');
        $subject = $request->input('subject');
        $body = $request->input('body');

        // Get members with valid emails (members only)
        $validMembers = User::whereIn('id', $memberIds)
            ->where('role', 'member')
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->pluck('id')
            ->toArray();

        $skippedCount = count($memberIds) - count($validMembers);

        if (empty($validMembers)) {
            return response()->json([
                'success' => false,
                'message' => '沒有可發送郵件的會員',
                'queued_count' => 0,
                'skipped_count' => $skippedCount,
            ], 422);
        }

        // Dispatch jobs in chunks of 50
        collect($validMembers)->chunk(50)->each(function ($chunk) use ($subject, $body) {
            SendBatchEmailJob::dispatch($chunk->values()->toArray(), $subject, $body);
        });

        $queuedCount = count($validMembers);

        return response()->json([
            'success' => true,
            'message' => "已排程發送 {$queuedCount} 封郵件" . ($skippedCount > 0 ? "（{$skippedCount} 位會員無有效 Email）" : ''),
            'queued_count' => $queuedCount,
            'skipped_count' => $skippedCount,
        ]);
    }

    /**
     * Get count of members matching the current filter.
     */
    public function count(Request $request): JsonResponse
    {
        $search = $request->input('search');
        $courseId = $request->input('course_id');

        $query = User::query()
            ->where('role', 'member');

        // Apply search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                  ->orWhere('real_name', 'like', "%{$search}%")
                  ->orWhere('nickname', 'like', "%{$search}%");
            });
        }

        // Apply course ownership filter
        if ($courseId) {
            $query->whereHas('purchases', function ($q) use ($courseId) {
                $q->where('course_id', $courseId)
                  ->where('status', 'completed');
            });
        }

        return response()->json([
            'count' => $query->count(),
        ]);
    }
}
