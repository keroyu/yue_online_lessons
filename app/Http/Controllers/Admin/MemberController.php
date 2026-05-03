<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GiftCourseRequest;
use App\Http\Requests\Admin\SendBatchEmailRequest;
use App\Http\Requests\Admin\UpdateMemberRequest;
use App\Mail\BatchEmailMail;
use App\Mail\CourseGiftedMail;
use App\Models\Course;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
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
            ->members();

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
                  ->paidStatus();
            });
        }

        // Get matching count before pagination
        $matchingCount = $query->count();

        // Apply sorting and pagination
        $members = $query->orderBy($sortField, $sortDirection)
            ->paginate($perPage)
            ->withQueryString();

        // Get all courses for the filter dropdown and gift course modal
        $courses = Course::select('id', 'name', 'description')
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
        if (!$member->isMember()) {
            abort(404, '找不到該會員');
        }

        // Load courses with progress calculation
        $courses = $member->purchases()
            ->with(['course.lessons'])
            ->paidStatus()
            ->get()
            ->values();

        $progressMap = $member->lessonProgress()
            ->pluck('lesson_id')
            ->flip()
            ->toArray();

        $courses = $courses
            ->map(function ($purchase) use ($member, $progressMap) {
                $course = $purchase->course;
                $progress = $member->getCourseProgressSummary($course, $progressMap);

                return [
                    'id' => $course->id,
                    'name' => $course->name,
                    'purchased_at' => $purchase->created_at->toIso8601String(),
                    'acquisition_type' => in_array($purchase->type, ['gift', 'system_assigned']) ? 'gift' : 'paid',
                    'total_lessons' => $progress['total_lessons'],
                    'completed_lessons' => $progress['completed_lessons'],
                    'progress_percent' => $progress['progress_percent'],
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
        if (!$member->isMember()) {
            abort(403, '只能編輯會員資料');
        }

        $member->update($request->validated());

        // Return JSON for AJAX requests (modal), redirect for Inertia requests (inline edit)
        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => '會員資料更新成功',
            ]);
        }

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
        $members = User::whereIn('id', $memberIds)
            ->members()
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->get();

        $skippedCount = count($memberIds) - $members->count();

        if ($members->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => '沒有可發送郵件的會員',
                'queued_count' => 0,
                'skipped_count' => $skippedCount,
            ], 422);
        }

        $sentCount = 0;
        foreach ($members as $member) {
            try {
                Mail::to($member->email)->send(new BatchEmailMail($subject, $body));
                $sentCount++;
            } catch (\Exception $e) {
                Log::error('Failed to send batch email', [
                    'member_id' => $member->id,
                    'email' => $member->email,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => "已成功發送 {$sentCount} 封郵件" . ($skippedCount > 0 ? "（{$skippedCount} 位會員無有效 Email）" : ''),
            'queued_count' => $sentCount,
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
            ->members();

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
                  ->paidStatus();
            });
        }

        return response()->json([
            'count' => $query->count(),
        ]);
    }

    /**
     * Gift a course to selected members.
     */
    public function giftCourse(GiftCourseRequest $request): JsonResponse
    {
        $memberIds = $request->input('member_ids');
        $courseId = $request->input('course_id');

        // Get valid members (members only)
        $validMembers = User::whereIn('id', $memberIds)
            ->members()
            ->get();

        if ($validMembers->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => '沒有有效的會員可以贈送課程',
                'gifted_count' => 0,
                'already_owned_count' => 0,
                'email_queued_count' => 0,
                'skipped_no_email_count' => 0,
            ], 422);
        }

        // Check which members already own the course (excluding refunded purchases)
        $alreadyOwnedIds = Purchase::whereIn('user_id', $validMembers->pluck('id'))
            ->where('course_id', $courseId)
            ->paidStatus()
            ->pluck('user_id')
            ->toArray();

        // Filter out members who already own the course
        $membersToGift = $validMembers->filter(function ($member) use ($alreadyOwnedIds) {
            return !in_array($member->id, $alreadyOwnedIds);
        });

        $alreadyOwnedCount = count($alreadyOwnedIds);

        // Edge case: All selected members already own the course
        if ($membersToGift->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => '所有選取的會員都已擁有此課程',
                'gifted_count' => 0,
                'already_owned_count' => $alreadyOwnedCount,
                'email_queued_count' => 0,
                'skipped_no_email_count' => 0,
            ]);
        }

        // Load the course for email notification
        $course = Course::find($courseId);
        $courseDescription = $course->description ?: '（無課程簡介）';

        $giftedCount = 0;
        $emailSentCount = 0;
        $skippedNoEmailCount = 0;

        foreach ($membersToGift as $member) {
            try {
                // Create or update gift purchase (handles refunded purchases via unique constraint)
                Purchase::updateOrCreate(
                    ['user_id' => $member->id, 'course_id' => $courseId],
                    [
                        'buyer_email' => $member->email ?? '',
                        'amount' => 0,
                        'currency' => 'TWD',
                        'status' => 'paid',
                        'type' => 'gift',
                    ]
                );
                $giftedCount++;

                // Send notification email if member has email
                if ($member->email) {
                    Mail::to($member->email)->send(new CourseGiftedMail($course->name, $courseDescription));
                    $emailSentCount++;
                } else {
                    $skippedNoEmailCount++;
                }
            } catch (\Exception $e) {
                Log::error('Failed to gift course to member', [
                    'member_id' => $member->id,
                    'course_id' => $courseId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Build result message
        $message = "已成功贈送課程給 {$giftedCount} 位會員";
        $warnings = [];

        if ($alreadyOwnedCount > 0) {
            $warnings[] = "{$alreadyOwnedCount} 位已擁有課程";
        }
        if ($skippedNoEmailCount > 0) {
            $warnings[] = "{$skippedNoEmailCount} 位無 Email 未發送通知";
        }

        if (!empty($warnings)) {
            $message .= '（' . implode('、', $warnings) . '）';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'gifted_count' => $giftedCount,
            'already_owned_count' => $alreadyOwnedCount,
            'email_queued_count' => $emailSentCount,
            'skipped_no_email_count' => $skippedNoEmailCount,
        ]);
    }

    /**
     * Export members as a CSV download.
     */
    public function exportCsv(Request $request): HttpResponse|\Symfony\Component\HttpFoundation\StreamedResponse
    {
        $scope = $request->input('scope', 'all');
        $ids = $request->input('ids', []);
        $search = $request->input('search');
        $courseId = $request->input('course_id');

        if ($scope === 'selected' && empty($ids)) {
            abort(422, '請先選取要匯出的會員');
        }

        $query = User::query()->members();

        if ($scope === 'selected') {
            $query->whereIn('id', $ids);
        } else {
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('email', 'like', "%{$search}%")
                      ->orWhere('real_name', 'like', "%{$search}%")
                      ->orWhere('nickname', 'like', "%{$search}%");
                });
            }
            if ($courseId) {
                $query->whereHas('purchases', function ($q) use ($courseId) {
                    $q->where('course_id', $courseId)->paidStatus();
                });
            }
        }

        $filename = 'members-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for Excel compatibility
            fwrite($handle, "\xEF\xBB\xBF");

            // Header row
            fputcsv($handle, ['暱稱', '真實姓名', 'Email', '加入日期', '最後登入時間']);

            $query->orderBy('created_at', 'desc')
                ->select(['nickname', 'real_name', 'email', 'created_at', 'last_login_at'])
                ->chunk(200, function ($members) use ($handle) {
                    foreach ($members as $member) {
                        fputcsv($handle, [
                            $member->nickname ?? '',
                            $member->real_name ?? '',
                            $member->email ?? '',
                            $member->created_at ? $member->created_at->format('Y-m-d') : '',
                            $member->last_login_at ? $member->last_login_at->format('Y-m-d H:i') : '',
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Import members from a pasted list of email addresses.
     */
    public function importEmails(Request $request): JsonResponse
    {
        $request->validate([
            'emails' => 'required|string|max:50000',
        ], [
            'emails.required' => '請輸入至少一個 Email 地址',
            'emails.max' => '輸入內容過長，請分批匯入',
        ]);

        $raw = $request->input('emails', '');

        // Split by newlines and commas, trim, de-duplicate
        $lines = preg_split('/[\r\n,]+/', $raw);
        $emails = array_unique(array_filter(array_map('trim', $lines)));

        if (empty($emails)) {
            return response()->json([
                'errors' => ['emails' => ['請輸入至少一個 Email 地址']],
            ], 422);
        }

        $createdCount = 0;
        $skippedCount = 0;
        $invalidCount = 0;
        $invalidEmails = [];

        foreach ($emails as $email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $invalidCount++;
                $invalidEmails[] = $email;
                continue;
            }

            $email = strtolower($email);

            if (User::where('email', $email)->exists()) {
                $skippedCount++;
                continue;
            }

            User::create([
                'email' => $email,
                'nickname' => Str::before($email, '@'),
                'role' => 'member',
                'email_verified_at' => now(),
            ]);

            $createdCount++;
        }

        $parts = ["新增 {$createdCount} 位會員"];
        if ($skippedCount > 0) {
            $parts[] = "略過 {$skippedCount} 位（已存在）";
        }
        if ($invalidCount > 0) {
            $parts[] = "無效格式 {$invalidCount} 個";
        }

        return response()->json([
            'success' => true,
            'created_count' => $createdCount,
            'skipped_count' => $skippedCount,
            'invalid_count' => $invalidCount,
            'invalid_emails' => $invalidEmails,
            'message' => implode('，', $parts),
        ]);
    }
}
