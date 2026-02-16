<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Update preorder courses to selling when sale_at time is reached
Schedule::command('courses:update-status')->everyMinute();

// Process and send scheduled drip emails daily at 9:00 AM
Schedule::command('drip:process-emails')->dailyAt('09:00');
