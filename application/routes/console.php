<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('auth:clear-resets')->daily();
Schedule::command('queue:work --stop-when-empty')->everyMinute();
Schedule::command('backup:run')->weeklyOn(0, '03:00');
