<?php

namespace App\Services;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Hands the admin panel to the first person who registers on a fresh install
 * (000 US13).
 *
 * A clean database has no administrator, and the panel is the only place to
 * configure the site — so a new install is unusable until somebody edits
 * `users.role` by hand over SSH. That is fine for one site and untenable for a
 * copy of this codebase somebody else installed.
 *
 * The rule deliberately has two halves (FR-115). "No admin exists" alone would
 * hand the panel to the next stranger to sign up on an established site whose
 * administrator was deleted. "The users table is empty" alone would not work
 * either, because webhooks and newsletter subscriptions create members before
 * the owner ever logs in.
 */
class FirstAdminService
{
    /**
     * Promote $user when this install still has no administrator.
     *
     * Returns whether the promotion happened. Callers ignore the result in
     * normal operation — registration must succeed either way (FR-116).
     */
    public function bootstrap(User $user): bool
    {
        if (! $this->shouldPromote($user)) {
            return false;
        }

        $user->update(['role' => 'admin']);

        // The support address is what visitors write to and what every mail
        // template prints. Only filled when empty: an install that already
        // answered this question keeps its answer.
        if (trim((string) SiteSetting::get(SiteSetting::SUPPORT_EMAIL_KEY, '')) === '') {
            SiteSetting::set(SiteSetting::SUPPORT_EMAIL_KEY, $user->email);
        }

        // This is the only code path in the app that creates an administrator
        // without anybody using the admin panel, so it says so out loud (FR-117).
        Log::info('first_admin.bootstrapped', [
            'user_id' => $user->id,
            'email'   => $user->email,
            'reason'  => $this->configuredEmail() !== '' ? 'configured_email' : 'first_account',
        ]);

        return true;
    }

    private function shouldPromote(User $user): bool
    {
        // An install that already has an administrator has already been set up.
        if (User::where('role', 'admin')->exists()) {
            return false;
        }

        $configured = $this->configuredEmail();

        if ($configured !== '') {
            // Naming an address means "only this person" — being first no
            // longer counts for anybody else.
            return mb_strtolower(trim($user->email)) === $configured;
        }

        return User::count() === 1;
    }

    private function configuredEmail(): string
    {
        return mb_strtolower(trim((string) config('auth.first_admin_email', '')));
    }
}
