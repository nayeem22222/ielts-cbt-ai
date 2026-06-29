<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Recommended scheduler entry for expired listening attempts:
// Schedule::command('listening:attempts:auto-submit-expired --limit=100')->everyMinute();
