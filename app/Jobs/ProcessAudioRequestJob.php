<?php

namespace App\Jobs;

use App\Repositories\ServiceRequestRepository;
use App\Services\ProcessServiceRequestService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessAudioRequestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $serviceRequestId) {}

    public function handle(
        ProcessServiceRequestService $processor,
        ServiceRequestRepository $requests,
    ): void {
        $request = $requests->findById($this->serviceRequestId);

        if (! $request) {
            return;
        }

        $processor->process($request);
    }
}
