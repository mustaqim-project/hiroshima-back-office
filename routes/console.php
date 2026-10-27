<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/**
 * @req FSD-09, FSD-18, RPT-23, RPT-24
 */

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Scheduler Hostinger Single Cron
Schedule::command('shifts:auto-close')->everyFiveMinutes();
Schedule::command('fraud:scan')->everyFifteenMinutes();
Schedule::command('audit:verify')->dailyAt('02:00');
