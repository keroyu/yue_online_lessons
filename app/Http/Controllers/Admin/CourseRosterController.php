<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ConsumeBundleCreditRequest;
use App\Models\Course;
use App\Services\BundleCreditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Student roster for one course, plus the batch credit deduction that goes with
 * it (011 US37 / FR-208–FR-211).
 *
 * The list is built server side on purpose (D144): the consume endpoint below
 * changes other people's credits, so it cannot take the browser's word for who
 * qualifies — it re-reads the purchases itself.
 */
class CourseRosterController extends Controller
{
    public function index(Request $request, Course $course, BundleCreditService $credits): JsonResponse
    {
        return response()->json([
            'has_bundle' => (bool) $course->has_bundle,
            'bundle_name' => $course->bundle_name,
            // The dropdown's options come with the list so the modal never has
            // to guess a tier id from a name.
            'plans' => $course->plans()->get(['id', 'name', 'bundle_unlimited']),
            'students' => $credits->roster(
                $course,
                $request->query('plan_id', 'all'),
                $request->boolean('with_credit'),
            ),
        ]);
    }

    public function consume(
        ConsumeBundleCreditRequest $request,
        Course $course,
        BundleCreditService $credits,
    ): JsonResponse {
        if (! $course->has_bundle) {
            return response()->json(['message' => '此課程未設定附帶福利'], 422);
        }

        return response()->json($credits->consume($course, $request->validated('user_ids')));
    }

    /**
     * Backfill credits for students who already hold the course (FR-219) —
     * the action a course needs right after a perk is added to it.
     */
    public function grant(
        ConsumeBundleCreditRequest $request,
        Course $course,
        BundleCreditService $credits,
    ): JsonResponse {
        if (! $course->has_bundle) {
            return response()->json(['message' => '此課程未設定附帶福利'], 422);
        }

        return response()->json($credits->grantToMembers($course, $request->validated('user_ids')));
    }
}
