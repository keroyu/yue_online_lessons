<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDripClaimRequest;
use App\Models\Course;
use App\Models\DripSubscription;
use App\Models\User;
use App\Services\DripService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class DripSubscriptionController extends Controller
{
    public function __construct(private DripService $dripService) {}

    /**
     * Guest claim — one post, no verification code, no login (010 US15).
     *
     * The code this replaced was gating `Auth::login()`, not the mailbox: it
     * proved ownership because the claim used to hand out a session. The claim
     * no longer does, so the code has nothing left to protect and the typo it
     * also caught is handled by the ten-second review on the form (FR-024).
     *
     * What remains guarded is the write side (D20): this request may add a
     * subscription, but it may not modify an account it has not authenticated.
     */
    public function subscribe(StoreDripClaimRequest $request): RedirectResponse
    {
        $email = $request->input('email');
        $courseId = $request->input('course_id');

        // Check if course is a drip course
        $course = Course::findOrFail($courseId);
        if ($course->course_type !== 'drip') {
            return back()->withErrors(['email' => '此商品不支援免費領取']);
        }

        // Check for existing subscription (including unsubscribed)
        $existingUser = User::where('email', $email)->first();
        if ($existingUser) {
            $existing = DripSubscription::where('user_id', $existingUser->id)
                ->where('course_id', $courseId)
                ->first();

            if ($existing && $existing->status === 'unsubscribed') {
                return back()->withErrors(['email' => '您已停止接收此商品的信件，無法再次領取']);
            }

            if ($existing) {
                // Not a dead end: the content was mailed to this address, so the
                // form tells them where to look (and how to claim on another one).
                return back()
                    ->with('drip_already_claimed', $email)
                    ->withErrors(['email' => '此 Email 已經領取過了，內容已寄到這個信箱']);
            }
        }

        $nickname = trim($request->input('nickname'));

        if ($existingUser) {
            // Never rename someone on the strength of an unauthenticated form —
            // fill the blank only (FR-025). This break did not exist while the
            // verification code stood in front of this line.
            if (blank($existingUser->nickname)) {
                $existingUser->update(['nickname' => $nickname]);
            }

            $user = $existingUser;
        } else {
            // email_verified_at stays null: nobody has proved anything yet, and
            // no gate on the site reads it. Writing now() would just be a lie
            // stored in a column (FR-025).
            $user = User::create([
                'email'    => $email,
                'nickname' => $nickname,
                'role'     => 'member',
            ]);
        }

        $result = $this->dripService->subscribe($user, $course);

        if (!$result['success']) {
            return back()->withErrors(['email' => $result['error']]);
        }

        return back()->with('drip_subscribed', true);
    }

    /**
     * Logged-in member one-click subscribe.
     */
    public function memberSubscribe(Request $request, Course $course): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'nickname' => ['required', 'string', 'max:50', 'regex:/\p{L}/u'],
        ], [
            'nickname.required' => '請輸入暱稱',
            'nickname.regex' => '暱稱需包含至少一個文字（不可為純空格或符號）',
        ]);

        $user->update(['nickname' => trim($validated['nickname'])]);

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
            return redirect()->route('home')->with('info', '您已停止接收此商品的信件');
        }

        $subscription->update([
            'status' => 'unsubscribed',
            'status_changed_at' => now(),
        ]);

        Log::info('Drip unsubscribed', [
            'subscription_id' => $subscription->id,
            'user_id' => $subscription->user_id,
        ]);

        return redirect()->route('home')->with('success', '已停止接收後續信件');
    }
}
