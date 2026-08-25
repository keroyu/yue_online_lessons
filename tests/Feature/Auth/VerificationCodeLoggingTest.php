<?php

namespace Tests\Feature\Auth;

use App\Services\VerificationCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Security regression: the OTP login code is the sole authentication factor, so
 * it must never be written to the application log in plaintext. Anyone able to
 * read the log during the code's 10-minute validity could otherwise take over
 * any account (VerificationCodeService.php:60).
 */
class VerificationCodeLoggingTest extends TestCase
{
    use RefreshDatabase;

    public function test_generated_code_is_not_written_to_logs(): void
    {
        Log::spy();

        $email  = 'victim@example.com';
        $result = app(VerificationCodeService::class)->generate($email);

        $this->assertTrue($result['success']);
        $code = $result['code'];

        // No log call — at any level — may contain the plaintext code.
        foreach (['debug', 'info', 'notice', 'warning', 'error'] as $level) {
            Log::shouldNotHaveReceived($level, function ($message, $context = []) use ($code) {
                return str_contains($message . json_encode($context), $code);
            });
        }
    }

    public function test_generation_is_still_logged_with_email_context(): void
    {
        Log::spy();

        $email = 'member@example.com';
        app(VerificationCodeService::class)->generate($email);

        Log::shouldHaveReceived('info')
            ->withArgs(function ($message, $context = []) use ($email) {
                return $message === 'Verification code generated'
                    && ($context['email'] ?? null) === $email;
            })
            ->once();
    }
}
