<?php

namespace App\Console\Commands;

use App\Services\HighTicketBookingService;
use Illuminate\Console\Command;

/**
 * Remind tomorrow's consultations (011 US19).
 *
 * Scheduled for 17:00 Taipei. Best-effort by design (FR-081): nothing
 * downstream depends on it — the booking, the slot, the Zoom meeting and the
 * calendar invite are all already settled facts — so a missing template or a
 * bounced address logs and moves on rather than turning the schedule red over
 * something nobody can act on.
 */
class SendConsultationReminders extends Command
{
    protected $signature = 'booking:send-reminders';

    protected $description = '寄送翌日（台灣時間）所有已確認面談的提醒信';

    public function handle(HighTicketBookingService $booking): int
    {
        $sent = $booking->sendDayBeforeReminders();

        $this->info("已寄出 {$sent} 封面談提醒");

        return self::SUCCESS;
    }
}
