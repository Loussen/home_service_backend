<?php

namespace App\Services;

use App\Jobs\ProcessAudioRequestJob;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Repositories\ServiceRequestRepository;
use App\Support\RequestFilters;
use App\Support\UrgentQuota;
use Illuminate\Http\UploadedFile;

class ServiceRequestService
{
    public function __construct(
        private readonly ServiceRequestRepository $requests,
        private readonly WalletService $walletService,
        private readonly ProcessServiceRequestService $processor,
    ) {}

    public function list(User $user)
    {
        return $this->requests->listForUser($user->id);
    }

    public function get(User $user, int $id): ServiceRequest
    {
        $request = $this->requests->findForUser($user->id, $id);
        abort_if(! $request, 404, 'Request not found');

        return $request;
    }

    public function createFromAudio(
        User $user,
        UploadedFile $audio,
        float $latitude,
        float $longitude,
        ?string $address = null,
        bool $isUrgent = false,
        ?int $categoryId = null,
        ?string $scheduledAt = null,
        ?string $timeSlot = null,
        ?int $childAge = null,
        ?bool $hasPet = null,
        ?float $budgetMax = null,
    ): ServiceRequest {
        abort_unless($user->isClient(), 403, 'Bu əməliyyat yalnız müştəri üçündür');
        if ($isUrgent) {
            UrgentQuota::assertCanCharge($user);
        }

        $path = $audio->store('audio/requests', 'public');
        $filters = RequestFilters::initialCriteria(
            $categoryId,
            $scheduledAt,
            $timeSlot,
            $childAge,
            $hasPet,
            $budgetMax,
        );

        $request = $this->requests->create([
            'user_id' => $user->id,
            'raw_audio_url' => $path,
            'category_id' => $filters['category_id'],
            'parsed_criteria' => $filters['parsed_criteria'],
            'latitude' => $latitude,
            'longitude' => $longitude,
            'address' => $address,
            'is_urgent' => false,
            'status' => 'processing',
        ]);

        if ($isUrgent) {
            $this->walletService->chargeUrgent($user, $request);
        }

        // Local/dev reliability: process inline so mobile can poll immediately.
        // Production with workers: set HOMESERVICE_AUDIO_SYNC=false
        if (config('homeservice.audio_sync', true)) {
            $request = $this->processor->process($request);
        } else {
            ProcessAudioRequestJob::dispatch($request->id);
        }

        return $this->requests->findForUser($user->id, $request->id) ?? $request;
    }

    public function createText(
        User $user,
        string $text,
        float $latitude,
        float $longitude,
        ?int $categoryId = null,
        ?string $address = null,
        bool $isUrgent = false,
        ?string $scheduledAt = null,
        ?string $timeSlot = null,
        ?int $childAge = null,
        ?bool $hasPet = null,
        ?float $budgetMax = null,
    ): ServiceRequest {
        abort_unless($user->isClient(), 403, 'Bu əməliyyat yalnız müştəri üçündür');
        if ($isUrgent) {
            UrgentQuota::assertCanCharge($user);
        }

        $filters = RequestFilters::initialCriteria(
            $categoryId,
            $scheduledAt,
            $timeSlot,
            $childAge,
            $hasPet,
            $budgetMax,
            $text,
        );

        $request = $this->requests->create([
            'user_id' => $user->id,
            'transcribed_text' => $text,
            'parsed_criteria' => $filters['parsed_criteria'],
            'category_id' => $filters['category_id'],
            'latitude' => $latitude,
            'longitude' => $longitude,
            'address' => $address,
            'is_urgent' => false,
            'status' => 'processing',
        ]);

        if ($isUrgent) {
            $this->walletService->chargeUrgent($user, $request);
        }

        $request = $this->processor->process($request);

        return $this->requests->findForUser($user->id, $request->id) ?? $request;
    }
}
