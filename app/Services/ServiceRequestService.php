<?php

namespace App\Services;

use App\Jobs\ProcessAudioRequestJob;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Repositories\ServiceRequestRepository;
use App\Support\RequestFilters;
use App\Support\RequestTtl;
use App\Support\UrgentQuota;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;

class ServiceRequestService
{
    public function __construct(
        private readonly ServiceRequestRepository $requests,
        private readonly WalletService $walletService,
        private readonly ProcessServiceRequestService $processor,
    ) {}

    public function list(
        User $user,
        int $page = 1,
        int $perPage = 10,
        string $filter = 'all',
    ): LengthAwarePaginator {
        RequestTtl::expireOverdue($user->id);

        return $this->requests->paginateForUser($user->id, $page, $perPage, $filter);
    }

    public function get(User $user, int $id): ServiceRequest
    {
        $request = $this->requests->findForUser($user->id, $id);
        abort_if(! $request, 404, 'Request not found');
        RequestTtl::expireIfNeeded($request);

        return $request->fresh(['category', 'matches.providerProfile.category', 'matches.providerProfile.categories', 'matches.providerProfile.user'])
            ?? $request;
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
        ?int $ttlHours = null,
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
        $hours = RequestTtl::resolveHours($ttlHours);

        $request = $this->requests->create([
            'user_id' => $user->id,
            'raw_audio_url' => $path,
            'category_id' => $filters['category_id'],
            'parsed_criteria' => array_merge($filters['parsed_criteria'] ?? [], [
                'ttl_hours' => $hours,
            ]),
            'latitude' => $latitude,
            'longitude' => $longitude,
            'address' => $address,
            'is_urgent' => false,
            'status' => 'processing',
            'expires_at' => RequestTtl::expiresAtFromNow($hours),
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
        ?int $ttlHours = null,
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
        $hours = RequestTtl::resolveHours($ttlHours);

        $request = $this->requests->create([
            'user_id' => $user->id,
            'transcribed_text' => $text,
            'parsed_criteria' => array_merge($filters['parsed_criteria'] ?? [], [
                'ttl_hours' => $hours,
            ]),
            'category_id' => $filters['category_id'],
            'latitude' => $latitude,
            'longitude' => $longitude,
            'address' => $address,
            'is_urgent' => false,
            'status' => 'processing',
            'expires_at' => RequestTtl::expiresAtFromNow($hours),
        ]);

        if ($isUrgent) {
            $this->walletService->chargeUrgent($user, $request);
        }

        $request = $this->processor->process($request);

        return $this->requests->findForUser($user->id, $request->id) ?? $request;
    }
}
