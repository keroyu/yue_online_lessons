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
