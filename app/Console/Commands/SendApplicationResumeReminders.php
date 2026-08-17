<?php

namespace App\Console\Commands;

use App\Services\HighTicketBookingService;
use Illuminate\Console\Command;

/**
 * Nudge applications that cleared the screening and then stopped (011 US26).
 *
 * Scheduled hourly inside Taipei daytime rather than once a day: the gap
 * between clearing the gate and giving up is measured in minutes, and a nudge
 * that lands the next evening is answering a question the person has stopped
 * asking. Best-effort like the day-before reminder — a missing template or a
 * bounced address logs and moves on (FR-136).
 */
class SendApplicationResumeReminders extends Command
{
    protected $signature = 'booking:send-resume-reminders';

    protected $description = '寄信提醒通過資格審核但未完成申請的人回來接著填';

    public function handle(HighTicketBookingService $booking): int
    {
        $sent = $booking->sendApplicationResumeReminders();

        $this->info("已寄出 {$sent} 封續填提醒");

        return self::SUCCESS;
    }
}
