<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SendBatchEmailRequest;
use App\Http\Requests\Admin\UpdateMemberRequest;
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
        // TODO: Implement in T022
        return response()->json([
            'member' => $member,
            'courses' => [],
        ]);
    }

    /**
     * Update the specified member's information.
     */
    public function update(UpdateMemberRequest $request, User $member)
    {
        // TODO: Implement in T014
        return back()->with('success', '會員資料更新成功');
    }

    /**
     * Send batch email to selected members.
     */
    public function sendBatchEmail(SendBatchEmailRequest $request): JsonResponse
    {
        // TODO: Implement in T038
        return response()->json([
            'success' => true,
            'message' => '已排程發送 0 封郵件',
            'queued_count' => 0,
            'skipped_count' => 0,
        ]);
    }

    /**
     * Get count of members matching the current filter.
     */
    public function count(Request $request): JsonResponse
    {
        // TODO: Implement in T029
        return response()->json([
            'count' => 0,
        ]);
    }
}
