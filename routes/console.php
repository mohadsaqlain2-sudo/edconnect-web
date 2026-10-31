<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled tasks (FR-23)
|--------------------------------------------------------------------------
| The server only needs ONE cron entry that runs Laravel's scheduler every
| minute; the scheduler then decides what is due:
|
|   * * * * * cd /path/to/educonnect && php artisan schedule:run >> /dev/null 2>&1
|
| On Windows you can simulate the same thing by running
|   php artisan schedule:work
*/
Schedule::command('classes:notify-starting-soon')
    ->everyMinute()
    // Never let two runs overlap if one is slow.
    ->withoutOverlapping();

// Close running classes once start time + duration has passed (FR-21).
Schedule::command('classes:complete-finished')
    ->everyMinute()
    ->withoutOverlapping();

// Daily database dump into storage/app/backups (SRS 5.4). The Linux
// scripts/backup.sh remains the full archive (database + uploads + .env).
Schedule::command('backup:database')
    ->dailyAt('02:30')
    ->withoutOverlapping();
