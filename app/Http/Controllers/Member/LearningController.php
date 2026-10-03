<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\LessonProgress;
use App\Services\BundleCreditService;
use App\Services\ConsultationCreditBookingService;
use App\Services\PointService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LearningController extends Controller
{
    public function index(
        Request $request,
        PointService $points,
        BundleCreditService $credits,
        ConsultationCreditBookingService $consultations,
    ): Response {
        $user = $request->user();

        // Get user's purchases with course data. Drip courses are reached via
        // subscription, so a Purchase on one (e.g. the creator's system_assigned
        // row) never lists it here (003 FR-044).
        $purchases = $user->purchases()
            ->with(['course.lessons', 'plan.lessons:id'])
            ->whereHas('course', fn ($q) => $q->where('course_type', '!=', 'drip'))
            ->paidStatus()
            ->orderBy('created_at', 'desc')
            ->get();

        // Get all user's progress records
        $progressMap = LessonProgress::where('user_id', $user->id)
            ->pluck('lesson_id')
            ->flip()
            ->toArray();

        // Upcoming self-booked consultations, one query for every card (011 US38).
        $activeSlots = $consultations->activeSlotLabels($purchases->pluck('id')->all());

        // Map to MyCourse format for frontend
        $courses = $purchases->map(function ($purchase) use ($progressMap, $user, $credits, $activeSlots) {
            $course = $purchase->course;
            // Tiered purchases only count their own lessons (011 FR-091).
            $progress = $user->getCourseProgressSummary($course, $progressMap, $purchase->accessibleLessonIds());

            return [
                'id' => $course->id,
                'name' => $course->name,
                'thumbnail' => $course->thumbnail_url,
                'instructor_name' => $course->instructor_name,
                'progress_percent' => $progress['progress_percent'],
                'purchased_at' => $purchase->created_at->toIso8601String(),
                'plan_name' => $purchase->plan?->name,
                // Bundle perk block (011 US37 / FR-207). Null on every course
                // without a perk, so the card renders exactly as before.
                'bundle' => $course->has_bundle ? [
                    'purchase_id' => $purchase->id,
                    'name' => $course->bundle_name,
                    'balance' => (int) $purchase->bundle_balance,
                    // Tells "never granted" apart from "all used up" (FR-218).
                    'granted' => (int) $purchase->bundle_granted,
                    'redeem_points' => $course->bundle_redeem_points,
                    // Read off the tier, so a tier flipped to unlimited shows up
                    // here immediately (011 D148).
                    'unlimited' => $credits->isUnlimitedFor($course, $purchase->plan),
                    // Ordinary courses book from the sales page (011 US38).
                    'self_booking' => $credits->isSelfBooking($course),
                    'booking_url' => route('course.show', $course) . '#consultation-booking',
                    'active_slot_label' => $activeSlots[$purchase->id] ?? null,
                ] : null,
            ];
        });

        return Inertia::render('Member/Learning', [
            'courses' => $courses,
            'availablePoints' => $points->availableBalance($user),
        ]);
    }
}
