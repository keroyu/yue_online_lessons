<?php

namespace App\Services;

use App\Models\Broadcast;
use App\Models\User;
use Illuminate\Support\Str;

class NewsletterService
{
    /**
     * Complete a subscription after the email has been OTP-verified.
     * Finds or creates the member, flips status to subscribed, mints an unsubscribe token.
     *
     * @return array{user: User, already: bool, created: bool}
     */
    public function subscribeVerified(string $email): array
    {
        $user = User::where('email', $email)->first();
        $created = $user === null;

        if ($created) {
            $user = User::create([
                'email' => $email,
                'role' => 'member',
            ]);
        }

        // email_verified_at is not fillable — set directly (verified via OTP).
        if (empty($user->email_verified_at)) {
            $user->email_verified_at = now();
        }

        $already = $user->newsletter_status === 'subscribed';

        if (! $already) {
            $user->newsletter_status = 'subscribed';
            $user->newsletter_subscribed_at = now();
            $user->newsletter_status_changed_at = now();
        }

        if (empty($user->newsletter_unsubscribe_token)) {
            $user->newsletter_unsubscribe_token = (string) Str::uuid();
        }

        $user->save();

        return ['user' => $user, 'already' => $already, 'created' => $created];
    }

    /**
     * Unsubscribe by token. Keeps the account/member status intact.
     */
    public function unsubscribeByToken(string $token): ?User
    {
        $user = User::where('newsletter_unsubscribe_token', $token)->first();

        if ($user === null) {
            return null;
        }

        if ($user->newsletter_status !== 'unsubscribed') {
            $user->newsletter_status = 'unsubscribed';
            $user->newsletter_status_changed_at = now();
            $user->save();
        }

        return $user;
    }

    /**
     * Record an open: stamp last_opened_at and auto-revive dormant subscribers. (FR-008)
     */
    public function recordOpen(User $user): void
    {
        $user->newsletter_last_opened_at = now();

        if ($user->newsletter_status === 'dormant') {
            $user->newsletter_status = 'subscribed';
            $user->newsletter_status_changed_at = now();
        }

        $user->save();
    }

    /**
     * Mark subscribers dormant when they were sent ≥1 broadcast in the window and
     * opened none of them. Never-mailed subscribers are exempt. (FR-008, US7)
     *
     * @return int number of subscribers moved to dormant
     */
    public function markDormantInactive(int $days = 60): int
    {
        $cutoff = now()->subDays($days);

        // Most recent broadcast send within the window; if none, nobody is eligible.
        $latestSentAt = Broadcast::where('status', 'sent')
            ->where('sent_at', '>=', $cutoff)
            ->max('sent_at');

        if ($latestSentAt === null) {
            return 0;
        }

        return User::where('newsletter_status', 'subscribed')
            ->whereNotNull('newsletter_subscribed_at')
            // Was already subscribed when at least one in-window broadcast went out.
            ->where('newsletter_subscribed_at', '<=', $latestSentAt)
            // No open recorded within the window.
            ->where(function ($q) use ($cutoff) {
                $q->whereNull('newsletter_last_opened_at')
                    ->orWhere('newsletter_last_opened_at', '<', $cutoff);
            })
            ->update([
                'newsletter_status' => 'dormant',
                'newsletter_status_changed_at' => now(),
            ]);
    }
}
