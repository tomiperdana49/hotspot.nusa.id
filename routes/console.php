<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('routers:heartbeat')->everyFiveMinutes();
Schedule::command('users:sync-first-login')->everyFiveMinutes();
Schedule::command('users:purge-expired')->everyFifteenMinutes();
Schedule::command('hotspot:sync-bindings')->everyMinute();
Schedule::command('history:prune --days=90')->dailyAt('03:30');
