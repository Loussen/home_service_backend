<?php

namespace App\Jobs;

use App\Services\PushNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NotifyMatchedProvidersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $serviceRequestId,
        public bool $force = false,
    ) {}

    public function handle(PushNotificationService $push): void
    {
        $push->sendForRequest($this->serviceRequestId, $this->force);
    }
}
