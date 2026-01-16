<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SendVerificationCodeRequest;
use App\Http\Requests\Auth\VerifyCodeRequest;
use App\Mail\VerificationCodeMail;
use App\Models\User;
use App\Services\VerificationCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    public function __construct(
        private VerificationCodeService $verificationCodeService
    ) {}

    public function showLoginForm(): Response
    {
        return Inertia::render('Auth/Login');
    }

    public function sendCode(SendVerificationCodeRequest $request): RedirectResponse
    {
        $email = $request->validated('email');

        $result = $this->verificationCodeService->generate($email);

        if (!$result['success']) {
            return back()->withErrors(['email' => $result['error']]);
        }

        // Send email
        try {
            Mail::to($email)->send(new VerificationCodeMail($result['code']));
        } catch (\Exception $e) {
            Log::error('Failed to send verification code', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors(['email' => '驗證碼發送失敗，請稍後重試']);
        }

        return back()->with('success', '驗證碼已發送至您的信箱');
    }

    public function verify(VerifyCodeRequest $request): RedirectResponse
    {
        $email = $request->validated('email');
        $code = $request->validated('code');

        // Check if user exists
        $user = User::where('email', $email)->first();
        $isNewUser = !$user;

        // If new user, require agreement to terms
        if ($isNewUser && !$request->boolean('agree_terms')) {
            return back()->withErrors(['agree_terms' => '請同意服務條款和隱私政策']);
        }

        // Validate code
        $result = $this->verificationCodeService->validate($email, $code);

        if (!$result['success']) {
            return back()->withErrors(['code' => $result['error']]);
        }

        // Create user if not exists
        if ($isNewUser) {
            $user = User::create([
                'email' => $email,
                'email_verified_at' => now(),
            ]);
        }

        // Update last login info
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        // Login user
        Auth::login($user, true);

        return redirect()->route('member.learning')->with('success', '登入成功');
    }

    public function logout(): RedirectResponse
    {
        Auth::logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('home')->with('success', '您已登出');
    }
}
