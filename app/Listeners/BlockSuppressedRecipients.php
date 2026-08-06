<?php

namespace App\Listeners;

use App\Models\EmailSuppression;
use App\Services\EmailSuppressionService;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Log;

/**
 * Single choke point for every outgoing mail (FR-022): rather than checking
 * suppression at each of the ~14 `Mail::to()` call sites — which future call
 * sites would silently miss — this listens on the framework's own
 * pre-send event, so nothing can reach an SMTP connection unchecked.
 */
class BlockSuppressedRecipients
{
    private const MAIL_CLASS_HEADER = 'X-Mail-Class';

    public function __construct(private EmailSuppressionService $suppressions) {}

    /**
     * Returning false cancels the send entirely. A mail with no header set is
     * treated as transactional (D23) — safer to over-send a confirmation than
     * to silently drop it because a caller forgot to tag it.
     */
    public function handle(MessageSending $event): bool
    {
        $headers = $event->message->getHeaders();
        $isMarketing = $headers->has(self::MAIL_CLASS_HEADER)
            && $headers->get(self::MAIL_CLASS_HEADER)->getBodyAsString() === 'marketing';

        if ($headers->has(self::MAIL_CLASS_HEADER)) {
            $headers->remove(self::MAIL_CLASS_HEADER);
        }

        $mailableClass = $event->data['__laravel_mailable'] ?? null;
        $blocked = false;

        foreach ($event->message->getTo() as $address) {
            $email = $address->getAddress();

            if (! $this->suppressions->blocks($email, $isMarketing)) {
                continue;
            }

            $blocked = true;

            Log::info('Blocked suppressed recipient', [
                'email'    => $email,
                'reason'   => EmailSuppression::reasonFor($email),
                'mailable' => $mailableClass,
            ]);
        }

        return ! $blocked;
    }
}
