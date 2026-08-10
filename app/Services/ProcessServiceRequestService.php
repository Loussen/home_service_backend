<?php

namespace App\Services;

use App\Models\ServiceRequest;
use App\Repositories\CategoryRepository;
use App\Repositories\ServiceRequestRepository;
use Illuminate\Support\Facades\Log;

class ProcessServiceRequestService
{
    public function __construct(
        private readonly AIService $ai,
        private readonly SearchService $search,
        private readonly CategoryRepository $categories,
        private readonly ServiceRequestRepository $requests,
    ) {}

    public function process(ServiceRequest $request, ?string $audioOverridePath = null): ServiceRequest
    {
        try {
            $text = $request->transcribed_text;

            if ($text === null || $text === '') {
                $path = $audioOverridePath ?? $request->raw_audio_url;
                if (! $path) {
                    throw new \RuntimeException('No audio or text to process');
                }
                $text = $this->ai->transcribe($path);
            }

            $parsed = $this->ai->parseRequestText($text);

            $categoryId = $request->category_id;
            if (! $categoryId && ! empty($parsed['category_slug'])) {
                $category = $this->categories->findBySlug($parsed['category_slug']);
                $categoryId = $category?->id;
            }

            $request = $this->requests->update($request, [
                'transcribed_text' => $text,
                'parsed_criteria' => $parsed,
                'category_id' => $categoryId,
                'status' => 'active',
            ]);

            $results = $this->search->matchRequest($request);

            if ($results->isNotEmpty()) {
                $request = $this->requests->update($request, ['status' => 'matched']);
            }

            Log::info('Service request processed', [
                'id' => $request->id,
                'matches' => $results->count(),
                'status' => $request->status,
            ]);

            return $request->load(['category', 'matches.providerProfile.category', 'matches.providerProfile.user']);
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
}
