<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Update preorder courses to selling when sale_at time is reached
Schedule::command('courses:update-status')->everyMinute();

// Publish scheduled posts whose published_at time has passed
Schedule::command('posts:publish-scheduled')->everyMinute();

// Dispatch scheduled newsletter broadcasts whose send time has arrived
Schedule::command('newsletter:send-scheduled')->everyMinute();

// Clean dormant newsletter subscribers (sent but never opened in 60 days) — monthly
Schedule::command('newsletter:clean-dormant')->monthlyOn(1, '02:00');

// Process and send scheduled drip emails daily at 9:00 AM
Schedule::command('drip:process-emails')->dailyAt('09:00');

// Backstop batch maturation of due referral rewards (on-read/on-spend already keeps
// active users correct; this covers dormant accounts + final consistency).
Schedule::command('points:mature')->dailyAt('00:30');

// Reconcile users.points cache against the ledger; logs any drift (SC-002).
Schedule::command('points:reconcile')->dailyAt('01:00');

// Release slot holds nobody confirmed AND delete the abandoned application
// (011 FR-035 / FR-068). The slot half is housekeeping — availability already
// ignores expired holds — but the lead deletion only happens here, so the
// Leads list stays wrong for as long as this does not run.
Schedule::command('booking:release-holds')->everyTenMinutes();

// Remind tomorrow's consultations (011 US19 / FR-076). The timezone is spelled
// out because the server runs UTC: the requirement is "17:00 Taiwan time", and
// the bare 17:00 above it would mean 01:00 in Taipei.
Schedule::command('booking:send-reminders')->timezone('Asia/Taipei')->dailyAt('17:00');

// Nudge unfinished applications (011 US26 / FR-136). Hourly rather than daily —
// the momentum this mail is trying to recover is measured in hours — but only
// inside waking hours, because the alternative is a 03:00 email about a form.
Schedule::command('booking:send-resume-reminders')
    ->timezone('Asia/Taipei')
    ->hourly()
    ->between('9:00', '21:00');
