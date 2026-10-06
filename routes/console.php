<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * Hostinger's shared hosting allows one cron entry, so everything the shop
 * does on a timer hangs off the scheduler. See DEPLOYMENT.md for the line.
 */
Schedule::command('ojasvi:release-unpaid')->everyFifteenMinutes()->withoutOverlapping();

/*
 * Where every parcel has got to. Hourly, because a courier scans a parcel a
 * few times a day and asking more often only spends the shop's rate limit.
 * Does nothing at all until Delhivery is set up.
 */
Schedule::command('ojasvi:track-parcels')->hourly()->withoutOverlapping();

// No daemon on shared hosting, so the queue is worked in short bursts rather
// than by a supervisor that cannot exist here.
Schedule::command('queue:work --stop-when-empty --max-time=55 --tries=3')
    ->everyMinute()
    ->withoutOverlapping();
