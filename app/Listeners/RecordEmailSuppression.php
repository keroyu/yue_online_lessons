<?php

namespace App\Listeners;

use App\Services\EmailSuppressionService;
use Illuminate\Support\Facades\Log;
use Resend\Laravel\Events\EmailBounced;
use Resend\Laravel\Events\EmailComplained;

/**
 * Turns Resend's webhook events into our own suppression facts. Only a
 * `Permanent` bounce is treated as "this address is dead" (FR-019) — a
 * `Transient`/`Undetermined` bounce means the mailbox was full or briefly
 * unreachable, not that it doesn't exist.
 */
class RecordEmailSuppression
{
    public function __construct(private EmailSuppressionService $suppressions) {}

    public function handleBounced(EmailBounced $event): void
    {
        $payload = $event->payload;
        $bounceType = $payload['data']['bounce']['type'] ?? null;
        $emails = $payload['data']['to'] ?? [];

        if ($bounceType !== 'Permanent') {
            Log::info('Resend bounce ignored (not Permanent)', [
                'to'   => $emails,
                'type' => $bounceType,
            ]);

            return;
        }

        $detail = $payload['data']['bounce']['subType'] ?? $payload['data']['bounce']['message'] ?? null;

        foreach ($emails as $email) {
            $this->suppressions->record($email, 'bounce', $detail);
        }
    }

    public function handleComplained(EmailComplained $event): void
    {
        $emails = $event->payload['data']['to'] ?? [];

        foreach ($emails as $email) {
            $this->suppressions->record($email, 'complaint');
        }
    }
}
