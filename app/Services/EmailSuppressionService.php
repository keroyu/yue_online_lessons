<?php

namespace App\Services;

use App\Models\EmailSuppression;

class EmailSuppressionService
{
    /**
     * Record a bounce/complaint fact. Idempotent: a repeat of the same event
     * (Svix resends) or a weaker event after a stronger one already on file is
     * a no-op. The only allowed transition is complaint → bounce (D21/FR-021).
     */
    public function record(string $email, string $reason, ?string $detail = null): void
    {
        $email = mb_strtolower(trim($email));
        $existing = EmailSuppression::where('email', $email)->first();

        if ($existing) {
            if ($existing->reason === 'complaint' && $reason === 'bounce') {
                $existing->update([
                    'reason'        => 'bounce',
                    'detail'        => $detail,
                    'suppressed_at' => now(),
                ]);
            }

            return;
        }

        EmailSuppression::create([
            'email'         => $email,
            'reason'        => $reason,
            'detail'        => $detail,
            'suppressed_at' => now(),
        ]);
    }

    /**
     * Whether a mail to $email must not go out. Bounce blocks everything —
     * the address cannot receive mail at all. Complaint only blocks marketing
     * mail — the recipient can still receive mail they themselves triggered
     * (verification code, booking confirmation) (D28).
     */
    public function blocks(string $email, bool $isMarketing): bool
    {
        $reason = EmailSuppression::reasonFor($email);

        if ($reason === null) {
            return false;
        }

        if ($reason === 'bounce') {
            return true;
        }

        return $isMarketing;
    }
}
