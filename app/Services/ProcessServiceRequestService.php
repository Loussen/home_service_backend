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
        private readonly GooglePlacesService $places,
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
                        // ASR may have worked — category/intent unclear. Do not pretend voice failed.
                        'missing_category' => true,
                        'transcription_failed' => $transcriptionFailed,
                    ]),
                    'status' => 'active',
                ]);

                return $this->wallet->refundUrgentIfNoResults($request);
            }

            // Prefer place spoken/written in the request; GPS is only a fallback pin.
            $location = $this->resolveRequestLocation($request, $resolved, $parsed);
            $parsed['location_source'] = $location['source'];

            $request = $this->requests->update($request, [
                'transcribed_text' => $displayText,
                'parsed_criteria' => $parsed,
                'category_id' => $categoryId,
                'address' => $location['address'],
                'latitude' => $location['latitude'],
                'longitude' => $location['longitude'],
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
     * Voice/text place first; client GPS only when AI found no usable place.
     *
     * @param  array{city_id: ?int, district_id: ?int, city: ?string, district: ?string}  $resolved
     * @param  array<string, mixed>  $parsed
     * @return array{latitude: float, longitude: float, address: ?string, source: string}
     */
    private function resolveRequestLocation(
        ServiceRequest $request,
        array $resolved,
        array $parsed,
    ): array {
        $fallbackLat = (float) $request->latitude;
        $fallbackLng = (float) $request->longitude;
        $fallbackAddress = $request->address;

        $spokenParts = array_filter([
            $resolved['district'] ?? null,
            $resolved['city'] ?? null,
        ]);
        $spokenLabel = $spokenParts !== []
            ? trim(implode(', ', $spokenParts))
            : null;

        // AI returned a place name — try to pin the map there.
        if ($spokenLabel !== null) {
            $query = $spokenLabel.', Azerbaijan';
            $geo = $this->places->geocode($query);
            if ($geo === null && ($resolved['district'] ?? null)) {
                $geo = $this->places->geocode(
                    ($resolved['district'] ?? '').', Bakı, Azerbaijan'
                );
            }

            if ($geo !== null) {
                return [
                    'latitude' => (float) $geo['latitude'],
                    'longitude' => (float) $geo['longitude'],
                    'address' => $geo['formatted_address']
                        ?: $spokenLabel
                        ?: $fallbackAddress,
                    'source' => 'ai_place',
                ];
            }

            // Geocode unavailable — still prefer spoken label for display;
            // keep GPS coords so matching has a pin (district_id still filters).
            return [
                'latitude' => $fallbackLat,
                'longitude' => $fallbackLng,
                'address' => $spokenLabel,
                'source' => 'ai_place_unresolved',
            ];
        }

        // No place in speech/text — use client GPS (and fill address if empty).
        $address = $fallbackAddress;
        if (! filled($address) && $this->places->isConfigured()) {
            $rev = $this->places->reverseGeocode($fallbackLat, $fallbackLng);
            $address = $rev['formatted_address'] ?? null;
        }

        return [
            'latitude' => $fallbackLat,
            'longitude' => $fallbackLng,
            'address' => $address,
            'source' => 'client_gps',
        ];
    }

    /**
     * Empty, too short, punctuation-only, or common Whisper silence hallucinations.
     */
    private function looksLikeFailedTranscript(string $text): bool
    {
        $t = mb_strtolower(trim($text));
        $t = preg_replace('/\s+/u', ' ', $t) ?? $t;
        // Strip trailing punctuation for exact-phrase checks.
        $bare = trim(preg_replace('/[.!?…,;:]+$/u', '', $t) ?? $t);

        if ($bare === '' || mb_strlen($bare) < 12) {
            return true;
        }
        if (! preg_match('/\p{L}/u', $bare)) {
            return true;
        }
        // Silence often yields Hindi/CJK hallucinations — require AZ/RU/EN letters.
        if (! preg_match('/[a-zа-яёəğıöüşç]/iu', $bare)) {
            return true;
        }

        // Prompt-echo of our location vocabulary: long comma lists / "City / District".
        $commaCount = substr_count($bare, ',');
        $slashPlaceCount = preg_match_all('/\s\/\s/u', $bare) ?: 0;
        if ($commaCount >= 6 || $slashPlaceCount >= 3) {
            return true;
        }
        if (str_contains($bare, 'azerbaijan place names')
            || str_contains($bare, 'home-service request vocabulary')
            || str_contains($bare, 'transcribe speech only')
        ) {
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
            // Former STT prompt echo on silence (service words used to be in prompt).
            'uşaq dayəsi lazımdır',
            'uşaq dayəsi axtarılır',
            'usaq dayesi lazimdir',
            'körpə dayəsi lazımdır',
            'məktəbli dayəsi lazımdır',
            'dayə lazımdır',
            'daye lazimdir',
            'təmizlik lazımdır',
            'it gəzdirmə lazımdır',
            'nanny needed',
            'child nanny needed',
        ];
        foreach ($hallucinations as $bad) {
            if ($bare === $bad || $t === $bad) {
                return true;
            }
        }

        // Ultra-short generic service-only lines with no place/time/number —
        // typical silence/prompt echo, not a real spoken request.
        if (mb_strlen($bare) <= 28
            && preg_match('/^(uşaq\s+)?day[eə]si?\s+(lazımdır|axtarılır)$/u', $bare)
        ) {
            return true;
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
