<?php

use App\Jobs\SyncEmailAccountJob;
use App\Models\EmailAccount;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    EmailAccount::where('status', 'active')
        ->each(function ($account) {
            SyncEmailAccountJob::dispatch($account->id);
        });
})->everyFiveMinutes()
    ->name('sync-all-accounts')
    ->withoutOverlapping();
