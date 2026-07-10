<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNewsletterSubscriptionRequest;
use App\Mail\NewsletterWelcomeMail;
use App\Mail\VerificationCodeMail;
use App\Models\User;
use App\Services\NewsletterService;
use App\Services\VerificationCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class NewsletterSubscriptionController extends Controller
{
    public function __construct(
        private NewsletterService $newsletterService,
        private VerificationCodeService $verificationCodeService,
    ) {}

    /**
     * Step 1: send a verification code (unless already subscribed).
     */
    public function subscribe(StoreNewsletterSubscriptionRequest $request): RedirectResponse
    {
        $email = $request->input('email');
        $authUser = $request->user();

        // Logged-in member subscribing their own (already-verified) email: no OTP needed —
        // identity is already proven, so subscribe in one step.
        if ($authUser && strcasecmp($authUser->email, $email) === 0) {
            if ($authUser->newsletter_status === 'subscribed') {
                return back()->with('newsletter_info', '你已在訂閱清單中');
            }

            $outcome = $this->newsletterService->subscribeVerified($email);
            $this->sendWelcome($outcome['user']);

            return back()->with('newsletter_subscribed', true);
        }

        $existing = User::where('email', $email)->first();
        if ($existing && $existing->newsletter_status === 'subscribed') {
            return back()->with('newsletter_info', '你已在訂閱清單中');
        }

        $result = $this->verificationCodeService->generate($email);
        if (! $result['success']) {
            return back()->withErrors(['email' => $result['error']]);
        }

        try {
            Mail::to($email)->send(new VerificationCodeMail($result['code']));
        } catch (\Throwable $e) {
            Log::error('Newsletter verification code send failed', ['email' => $email, 'error' => $e->getMessage()]);
            return back()->withErrors(['email' => '驗證碼發送失敗，請稍後重試']);
        }

        return back()->with([
            'newsletter_code_sent' => true,
            'newsletter_email' => $email,
        ]);
    }

    /**
     * Step 2: verify the code, create/attach the member, subscribe, send welcome mail.
     */
    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'string'],
        ], [
            'code.required' => '請輸入驗證碼',
        ]);

        $email = $request->input('email');

        $result = $this->verificationCodeService->validate($email, $request->input('code'));
        if (! $result['success']) {
            return back()->withErrors(['code' => $result['error']]);
        }

        $outcome = $this->newsletterService->subscribeVerified($email);
        $user = $outcome['user'];

        Auth::login($user, true);
        $user->update(['last_login_at' => now(), 'last_login_ip' => $request->ip()]);

        if ($outcome['already']) {
            return back()->with('newsletter_info', '你已在訂閱清單中');
        }

        $this->sendWelcome($user);

        return back()->with('newsletter_subscribed', true);
    }

    private function sendWelcome(User $user): void
    {
        try {
            Mail::to($user->email)->send(new NewsletterWelcomeMail($user));
        } catch (\Throwable $e) {
            Log::warning('Newsletter welcome mail failed', ['user' => $user->id, 'error' => $e->getMessage()]);
        }
    }

    public function showUnsubscribe(string $token): Response
    {
        $user = User::where('newsletter_unsubscribe_token', $token)->firstOrFail();

        return Inertia::render('Newsletter/Unsubscribe', [
            'token' => $token,
            'email' => $user->email,
            'status' => $user->newsletter_status,
        ]);
    }

    public function unsubscribe(string $token): RedirectResponse
    {
        $user = $this->newsletterService->unsubscribeByToken($token);

        if ($user === null) {
            return redirect()->route('home')->with('error', '退訂連結無效');
        }

        return redirect()->route('home')->with('success', '已成功退訂電子報');
    }
}
