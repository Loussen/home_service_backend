<?php

use App\Support\RequestTtl;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(static function () {
    // Expire first (API traffic may have expired some already without notify).
    RequestTtl::expireOverdue();
    // Then nudge any expired match that still needs the marketing push.
    app(\App\Services\PushNotificationService::class)
        ->notifyPendingMissedOpportunities();
})->everyFiveMinutes()->name('expire-service-requests');
