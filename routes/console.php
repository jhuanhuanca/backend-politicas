<?php

use App\Domain\News\Jobs\IngestNewsFeeds;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::job(new IngestNewsFeeds)->hourly();
Schedule::command('horizon:snapshot')->everyFiveMinutes();
Schedule::command('backup:database')->daily();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
