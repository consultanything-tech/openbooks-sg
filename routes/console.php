<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Tasks
|--------------------------------------------------------------------------
| Add a single cron entry to your server:
|   * * * * * cd /path-to-app && php artisan schedule:run >> /dev/null 2>&1
*/

// Generate recurring invoices & bills daily at 6:00 AM
Schedule::command('recurring:generate')->dailyAt('06:00')->withoutOverlapping();

// Send payment reminder emails daily at 9:00 AM
Schedule::command('reminders:send')->dailyAt('09:00')->withoutOverlapping();

// Create automated database backup daily at 2:00 AM
Schedule::command('backup:run')->dailyAt('02:00')->withoutOverlapping();

