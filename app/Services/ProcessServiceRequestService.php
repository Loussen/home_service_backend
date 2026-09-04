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
        private readonly WalletService $wallet,
    ) {}

    public function process(ServiceRequest $request, ?string $audioOverridePath = null): ServiceRequest
    {
        try {
            $text = $request->transcribed_text;
            $transcriptionFailed = false;

            $locationHints = $this->locationHintList();

            if ($text === null || $text === '') {
                $path = $audioOverridePath ?? $request->raw_audio_url;
                if (! $path) {
                    throw new \RuntimeException('No audio or text to process');
                }
                try {
                    $text = $this->ai->transcribe($path, $locationHints);
                } catch (\Throwable $e) {
                    Log::warning('Whisper failed — same parse pipeline needs text', [
                        'id' => $request->id,
                        'error' => $e->getMessage(),
                    ]);
                    $transcriptionFailed = true;
                    $text = '';
                }
            }

            // Silence / gibberish / empty ASR — do not invent matches.
            if ($this->looksLikeFailedTranscript((string) $text)) {
                $transcriptionFailed = true;
                $text = '';
            }

            if ($transcriptionFailed && $text === '') {
                $request = $this->requests->update($request, [
                    'transcribed_text' => null,
                    'category_id' => null,
                    'parsed_criteria' => [
                        'transcription_failed' => true,
                        'raw_text' => '',
                    ],
                    'status' => 'active',
                ]);

                return $this->wallet->refundUrgentIfNoResults($request);
            }

            $catalog = $this->categories->leafCatalog();
            $parsed = $this->ai->parseRequestText($text, $catalog, $locationHints);

            // Show the human-readable corrected line when ASR was cleaned up.
            $displayText = $parsed['normalized_text'] ?? $text;
            $parsed['asr_raw_text'] = $text;

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

            // Unclear voice / no usable category — do not dump every nearby provider.
            if (! $categoryId) {
                $request = $this->requests->update($request, [
                    'transcribed_text' => $displayText !== '' ? $displayText : null,
                    'category_id' => null,
                    'parsed_criteria' => array_merge($parsed, [
                        'transcription_failed' => true,
                        'missing_category' => true,
                    ]),
                    'status' => 'active',
                ]);

                return $this->wallet->refundUrgentIfNoResults($request);
            }

            $address = $request->address;
            if (! $address && ($resolved['district'] || $resolved['city'])) {
                $address = trim(implode(', ', array_filter([
                    $resolved['district'],
                    $resolved['city'],
                ])));
            }

            $request = $this->requests->update($request, [
                'transcribed_text' => $displayText,
                'parsed_criteria' => $parsed,
                'category_id' => $categoryId,
                'address' => $address,
                'status' => 'active',
            ]);

            $results = $this->search->matchRequest($request);

            if ($results->isNotEmpty()) {
                $request = $this->requests->update($request, ['status' => 'matched']);
                $this->push->notifyNewMatches($request);
            } else {
                // No providers found — do not keep urgent fee / daily urgent slot.
                $request = $this->wallet->refundUrgentIfNoResults($request);
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

            $request = $this->requests->update($request, [
                'status' => 'active',
                'transcribed_text' => $request->transcribed_text ?? 'Processing failed',
            ]);

            return $this->wallet->refundUrgentIfNoResults($request);
        }
    }

    /**
     * Empty, too short, punctuation-only, or common Whisper silence hallucinations.
     */
    private function looksLikeFailedTranscript(string $text): bool
    {
        $t = mb_strtolower(trim($text));
        if ($t === '' || mb_strlen($t) < 12) {
            return true;
        }
        if (! preg_match('/\p{L}/u', $t)) {
            return true;
        }
        // Silence often yields Hindi/CJK hallucinations — require AZ/RU/EN letters.
        if (! preg_match('/[a-zа-яёəğıöüşç]/iu', $t)) {
            return true;
        }

        $hallucinations = [
            'thanks for watching',
            'thank you for watching',
            'thanks for listening',
            'subscribe',
            'подписывайтесь',
            'продолжение следует',
            'amara.org',
            'www.youtube.com',
        ];
        foreach ($hallucinations as $bad) {
            if ($t === $bad || str_contains($t, $bad)) {
                return true;
            }
        }

        return false;
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
