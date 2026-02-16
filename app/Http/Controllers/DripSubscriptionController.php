<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\DripSubscription;
use App\Models\User;
use App\Services\DripService;
use App\Services\VerificationCodeService;
use App\Mail\VerificationCodeMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class DripSubscriptionController extends Controller
{
    public function __construct(
        private DripService $dripService,
        private VerificationCodeService $verificationCodeService
    ) {}

    /**
     * Step 1: Guest sends verification code to subscribe.
     */
    public function subscribe(Request $request): RedirectResponse
    {
        $request->validate([
            'course_id' => ['required', 'exists:courses,id'],
            'email' => ['required', 'email'],
        ], [
            'email.required' => '請輸入 Email',
            'email.email' => '請輸入有效的 Email 格式',
        ]);

        $email = $request->input('email');
        $courseId = $request->input('course_id');

        // Check if course is a drip course
        $course = Course::findOrFail($courseId);
        if ($course->course_type !== 'drip') {
            return back()->withErrors(['email' => '此課程不支援訂閱']);
        }

        // Check for existing subscription (including unsubscribed)
        $existingUser = User::where('email', $email)->first();
        if ($existingUser) {
            $existing = DripSubscription::where('user_id', $existingUser->id)
                ->where('course_id', $courseId)
                ->first();

            if ($existing && $existing->status === 'unsubscribed') {
                return back()->withErrors(['email' => '此課程已無法再次訂閱']);
            }

            if ($existing) {
                return back()->withErrors(['email' => '此 Email 已訂閱此課程']);
            }
        }

        // Generate and send verification code
        $result = $this->verificationCodeService->generate($email);

        if (!$result['success']) {
            return back()->withErrors(['email' => $result['error']]);
        }

        try {
            Mail::to($email)->send(new VerificationCodeMail($result['code']));
        } catch (\Exception $e) {
            Log::error('Failed to send drip verification code', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
            return back()->withErrors(['email' => '驗證碼發送失敗，請稍後重試']);
        }

        return back()->with([
            'success' => '驗證碼已發送至您的信箱',
            'drip_email' => $email,
            'drip_course_id' => $courseId,
        ]);
    }

    /**
     * Step 2: Guest verifies code and completes subscription.
     */
    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'course_id' => ['required', 'exists:courses,id'],
            'email' => ['required', 'email'],
            'code' => ['required', 'string'],
        ], [
            'code.required' => '請輸入驗證碼',
        ]);

        $email = $request->input('email');
        $code = $request->input('code');
        $courseId = $request->input('course_id');

        // Validate verification code
        $result = $this->verificationCodeService->validate($email, $code);

        if (!$result['success']) {
            return back()->withErrors(['code' => $result['error']]);
        }

        // Get or create user
        $user = User::where('email', $email)->first();
        $isNewUser = !$user;

        if ($isNewUser) {
            $user = User::create([
                'email' => $email,
                'email_verified_at' => now(),
                'role' => 'member',
            ]);
        }

        // Login the user
        Auth::login($user, true);

        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        // Subscribe to drip course
        $course = Course::findOrFail($courseId);
        $subscribeResult = $this->dripService->subscribe($user, $course);

        if (!$subscribeResult['success']) {
            return redirect()->route('course.show', $course)
                ->withErrors(['email' => $subscribeResult['error']]);
        }

        return back()->with('drip_subscribed', true);
    }

    /**
     * Logged-in member one-click subscribe.
     */
    public function memberSubscribe(Request $request, Course $course): RedirectResponse
    {
        $user = $request->user();

        $result = $this->dripService->subscribe($user, $course);

        if (!$result['success']) {
            return back()->withErrors(['subscribe' => $result['error']]);
        }

        return back()->with('drip_subscribed', true);
    }

    /**
     * Show unsubscribe confirmation page.
     */
    public function showUnsubscribe(string $token): Response
    {
        $subscription = DripSubscription::where('unsubscribe_token', $token)
            ->with('course')
            ->firstOrFail();

        return Inertia::render('Drip/Unsubscribe', [
            'subscription' => [
                'id' => $subscription->id,
                'status' => $subscription->status,
                'course_name' => $subscription->course->name,
            ],
            'token' => $token,
        ]);
    }

    /**
     * Process unsubscribe.
     */
    public function unsubscribe(string $token): RedirectResponse
    {
        $subscription = DripSubscription::where('unsubscribe_token', $token)
            ->firstOrFail();

        if ($subscription->status === 'unsubscribed') {
            return redirect()->route('home')->with('info', '您已退訂此課程');
        }

        $subscription->update([
            'status' => 'unsubscribed',
            'status_changed_at' => now(),
        ]);

        Log::info('Drip unsubscribed', [
            'subscription_id' => $subscription->id,
            'user_id' => $subscription->user_id,
        ]);

        return redirect()->route('home')->with('success', '已成功退訂');
    }
}
