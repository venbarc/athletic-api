<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('atheletic:sync-appts')
    ->dailyAt('05:00')
    ->timezone('America/Los_Angeles')
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('atheletic:sync-eligibility-checks')
    ->dailyAt('05:10')
    ->timezone('America/Los_Angeles')
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('atheletic:sync-prior-auths')
    ->dailyAt('05:20')
    ->timezone('America/Los_Angeles')
    ->withoutOverlapping()
    ->runInBackground();

Artisan::command('athelas:sync-appts', function () {
    $this->call('atheletic:sync-appts');
})->purpose('Backward-compatible alias for atheletic:sync-appts');

Artisan::command('athelas:sync-eligibility-checks', function () {
    $this->call('atheletic:sync-eligibility-checks');
})->purpose('Backward-compatible alias for atheletic:sync-eligibility-checks');

Artisan::command('athelas:sync-prior-auths', function () {
    $this->call('atheletic:sync-prior-auths');
})->purpose('Backward-compatible alias for atheletic:sync-prior-auths');