<?php

namespace App\Services;

use App\Models\ServiceRequest;
use App\Repositories\CategoryRepository;
use App\Repositories\LocationRepository;
use App\Repositories\ServiceRequestRepository;
use App\Support\RequestFilters;
use Illuminate\Support\Facades\Log;

class ProcessServiceRequestService
{
    public function __construct(
        private readonly AIService $ai,
        private readonly SearchService $search,
        private readonly CategoryRepository $categories,
        private readonly LocationRepository $locations,
        private readonly ServiceRequestRepository $requests,
        private readonly PushNotificationService $push,
    ) {}

    public function process(ServiceRequest $request, ?string $audioOverridePath = null): ServiceRequest
    {
        try {
            $text = $request->transcribed_text;
            $transcriptionFailed = false;

            if ($text === null || $text === '') {
                $path = $audioOverridePath ?? $request->raw_audio_url;
                if (! $path) {
                    throw new \RuntimeException('No audio or text to process');
                }
                try {
                    $text = $this->ai->transcribe($path);
                } catch (\Throwable $e) {
                    Log::warning('Whisper failed — same parse pipeline needs text', [
                        'id' => $request->id,
                        'error' => $e->getMessage(),
                    ]);
                    $transcriptionFailed = true;
                    $text = '';
                }
            }

            if ($transcriptionFailed && $text === '') {
                return $this->requests->update($request, [
                    'transcribed_text' => null,
                    'parsed_criteria' => [
                        'transcription_failed' => true,
                        'raw_text' => '',
                    ],
                    'status' => 'active',
                ]);
            }

            $catalog = $this->categories->leafCatalog();
            $locationHints = $this->locationHintList();
            $parsed = $this->ai->parseRequestText($text, $catalog, $locationHints);

            $parsed = RequestFilters::mergeUserFilters(
                $parsed,
                $request->parsed_criteria ?? [],
                $request->category_id,
                fn (?string $hhmm) => $this->ai->slotFromClock($hhmm),
            );

            $resolved = $this->locations->resolveNames(
                $parsed['city'] ?? null,
                $parsed['district'] ?? null
            );
            $parsed = array_merge($parsed, $resolved);

            $categoryId = $request->category_id;
            if (! $categoryId && ! empty($parsed['category_slug'])) {
                $category = $this->categories->findBySlug($parsed['category_slug']);
                $categoryId = $category?->id;
            }

            $address = $request->address;
            if (! $address && ($resolved['district'] || $resolved['city'])) {
                $address = trim(implode(', ', array_filter([
                    $resolved['district'],
                    $resolved['city'],
                ])));
            }

            $request = $this->requests->update($request, [
                'transcribed_text' => $text,
                'parsed_criteria' => $parsed,
                'category_id' => $categoryId,
                'address' => $address,
                'status' => 'active',
            ]);

            $results = $this->search->matchRequest($request);

            if ($results->isNotEmpty()) {
                $request = $this->requests->update($request, ['status' => 'matched']);
                $this->push->notifyNewMatches($request);
            }

            Log::info('Service request processed', [
                'id' => $request->id,
                'matches' => $results->count(),
                'status' => $request->status,
            ]);

            return $request->load(['category', 'matches.providerProfile.category', 'matches.providerProfile.categories', 'matches.providerProfile.user']);
        } catch (\Throwable $e) {
            Log::error('ProcessServiceRequest failed', [
                'id' => $request->id,
                'error' => $e->getMessage(),
            ]);

            return $this->requests->update($request, [
                'status' => 'active',
                'transcribed_text' => $request->transcribed_text ?? 'Processing failed',
            ]);
        }
    }

    /**
     * @return list<string>
     */
    private function locationHintList(): array
    {
        $hints = [];
        foreach ($this->locations->citiesWithDistricts() as $city) {
            $hints[] = $city->name;
            foreach ($city->districts as $district) {
                $hints[] = $city->name.' / '.$district->name;
            }
        }

        return $hints;
    }
}
