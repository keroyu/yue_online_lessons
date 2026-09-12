<?php

namespace App\Services;

use App\Models\Broadcast;
use App\Models\Post;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Support\Str;

class NewsletterService
{
    /**
     * Complete a subscription after the email has been OTP-verified.
     *
     * @return array{user: User, already: bool, created: bool}
     */
    public function subscribeVerified(string $email): array
    {
        return $this->attach($email, verified: true);
    }

    /**
     * Subscribe without proof of ownership — the homepage hero (002 US21).
     *
     * The single difference from the OTP path is that `email_verified_at` stays
     * null (012 FR-015): nobody has proved anything here, and writing the column
     * anyway would store a claim the login flow reads as true. This path adds a
     * list row, never an identity — which is the whole reason it is allowed to
     * skip the code (002 D61).
     *
     * @return array{user: User, already: bool, created: bool}
     */
    public function subscribeUnverified(string $email, ?string $nickname = null): array
    {
        return $this->attach($email, verified: false, nickname: $nickname);
    }

    /**
     * The single write point for both subscribe paths (012 FR-015).
     *
     * @return array{user: User, already: bool, created: bool}
     */
    private function attach(string $email, bool $verified, ?string $nickname = null): array
    {
        $user = User::where('email', $email)->first();
        $created = $user === null;

        if ($created) {
            $user = User::create([
                'email' => $email,
                'role' => 'member',
            ]);
        }

        // Never rename someone on the strength of an unverified form — fill the
        // blank only (012 FR-015, same rule as the drip claim in 010 FR-025).
        if (filled($nickname) && blank($user->nickname)) {
            $user->nickname = trim($nickname);
        }

        // email_verified_at is not fillable — set directly, and only when the
        // caller actually verified the address.
        if ($verified && empty($user->email_verified_at)) {
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

        if ($created) {
            // New member via newsletter signup → CompleteRegistration (000 US7).
            $meta = app(MetaConversionsService::class);
            $meta->send('CompleteRegistration', array_merge($meta->userDataFromRequest(request()), [
                'em'          => $meta->hashEmail($email),
                'external_id' => (string) $user->id,
            ]), [
                'content_name' => 'newsletter',
            ]);
        }

        return ['user' => $user, 'already' => $already, 'created' => $created];
    }

    /**
     * The article the welcome mail sends (012 FR-016 / FR-017).
     *
     * `newsletter_welcome_post_id` is a setting value with no foreign key behind
     * it, so the choice is re-checked rather than trusted; the descent is
     * chosen → earliest published → null (caller falls back to the short
     * template). Earliest, not latest, because the welcome mail is sent for
     * years and its content should not change every time something is published
     * (D14).
     */
    public function welcomePost(): ?Post
    {
        $chosenId = SiteSetting::get('newsletter_welcome_post_id');

        if (filled($chosenId)) {
            $chosen = Post::published()->find((int) $chosenId);

            if ($chosen) {
                return $chosen;
            }
        }

        return Post::published()->orderBy('published_at')->first();
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
