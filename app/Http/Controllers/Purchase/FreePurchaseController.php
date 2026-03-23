<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Purchase;
use App\Services\DripService;
use App\Services\PayuniService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FreePurchaseController extends Controller
{
    public function __construct(
        protected PayuniService $payuniService,
        protected DripService $dripService
    ) {}

    /**
     * Process a free course enrollment (price = 0, no portaly_product_id).
     *
     * POST /api/purchase/free/{course}
     */
    public function store(Request $request, Course $course): JsonResponse
    {
        // Guard: only free courses without a payment gateway
        if ($course->portaly_product_id || $course->price > 0) {
            return response()->json(['error' => 'This course is not free'], 422);
        }

        // Guard: draft / unpublished courses
        if ($course->status === 'draft' || !$course->is_published) {
            return response()->json(['error' => 'Course not available'], 422);
        }

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'name'  => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:30'],
        ]);

        // If logged in, always use the auth user's email for the enrollment
        // but update name/phone from what they submitted
        $email = auth()->user()?->email ?? $validated['email'];
        $name  = $validated['name'];
        $phone = $validated['phone'];

        Log::info('Free enrollment: processing', [
            'course_id' => $course->id,
            'email'     => $email,
        ]);

        $user = $this->payuniService->getOrCreateUser($email, $name, $phone);

        // Idempotency: one purchase per user per course
        $existing = Purchase::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();

        if ($existing) {
            Log::info('Free enrollment: already enrolled', [
                'user_id'   => $user->id,
                'course_id' => $course->id,
            ]);
            return response()->json(['success' => true, 'already_enrolled' => true]);
        }

        $purchase = Purchase::create([
            'user_id'            => $user->id,
            'course_id'          => $course->id,
            'buyer_email'        => $email,
            'amount'             => 0,
            'currency'           => 'TWD',
            'status'             => 'paid',
            'type'               => 'paid',
            'source'             => 'free',
            'webhook_received_at' => now(),
        ]);

        Log::info('Free enrollment: purchase created', [
            'purchase_id' => $purchase->id,
            'user_id'     => $user->id,
            'course_id'   => $course->id,
        ]);

        // Auto-subscribe for drip courses
        if ($course->course_type === 'drip') {
            $this->dripService->subscribe($user, $course);
        }

        $this->dripService->checkAndConvert($user, $course);

        return response()->json(['success' => true]);
    }
}
