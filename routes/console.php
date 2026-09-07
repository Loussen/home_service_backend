<?php

use App\Support\RequestTtl;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(static function () {
    RequestTtl::expireOverdue();
})->everyFiveMinutes()->name('expire-service-requests');
