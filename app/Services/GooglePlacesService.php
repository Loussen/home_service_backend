<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GooglePlacesService
{
    private const DEFAULT_LAT = 40.4093;

    private const DEFAULT_LNG = 49.8671;

    private const DEFAULT_RADIUS_M = 250000.0;

    public function isConfigured(): bool
    {
        return filled($this->apiKey());
    }

    /** @return list<array{place_id: string, description: string}> */
    public function autocomplete(string $query, string $language = 'az'): array
    {
        if (! $this->isConfigured() || mb_strlen(trim($query)) < 2) {
            return [];
        }

        // Places API (New) — legacy /place/autocomplete is unavailable on new GCP projects.
        $response = Http::timeout(12)
            ->withHeaders([
                'X-Goog-Api-Key' => $this->apiKey(),
                'X-Goog-FieldMask' => 'suggestions.placePrediction.placeId,suggestions.placePrediction.text',
            ])
            ->post('https://places.googleapis.com/v1/places:autocomplete', [
                'input' => trim($query),
                'languageCode' => $language,
                'includedRegionCodes' => ['az'],
                'locationBias' => [
                    'circle' => [
                        'center' => [
                            'latitude' => self::DEFAULT_LAT,
                            'longitude' => self::DEFAULT_LNG,
                        ],
                        'radius' => self::DEFAULT_RADIUS_M,
                    ],
                ],
            ]);

        if (! $response->successful()) {
            Log::warning('Places autocomplete (New) failed', [
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 400),
            ]);

            return [];
        }

        $suggestions = $response->json('suggestions', []);
        if (! is_array($suggestions)) {
            return [];
        }

        $items = [];
        foreach ($suggestions as $suggestion) {
            if (! is_array($suggestion)) {
                continue;
            }
            $prediction = $suggestion['placePrediction'] ?? null;
            if (! is_array($prediction)) {
                continue;
            }
            $placeId = (string) ($prediction['placeId'] ?? '');
            if ($placeId === '' && isset($prediction['place'])) {
                $placeId = preg_replace('#^places/#', '', (string) $prediction['place']) ?? '';
            }
            if ($placeId === '') {
                continue;
            }
            $text = $prediction['text'] ?? null;
            $description = is_array($text)
                ? (string) ($text['text'] ?? '')
                : (string) $text;

            $items[] = [
                'place_id' => $placeId,
                'description' => $description,
            ];
        }

        return $items;
    }

    /** @return array<string, mixed>|null */
    public function details(string $placeId, string $language = 'az'): ?array
    {
        if (! $this->isConfigured() || $placeId === '') {
            return null;
        }

        $placeId = preg_replace('#^places/#', '', $placeId) ?? $placeId;
        $response = Http::timeout(12)
            ->withHeaders([
                'X-Goog-Api-Key' => $this->apiKey(),
                'X-Goog-FieldMask' => 'id,formattedAddress,addressComponents,location',
            ])
            ->get('https://places.googleapis.com/v1/places/'.rawurlencode($placeId), [
                'languageCode' => $language,
            ]);

        if (! $response->successful()) {
            Log::warning('Places details (New) failed', [
                'place_id' => $placeId,
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 400),
            ]);

            return null;
        }

        $result = $response->json();
        if (! is_array($result)) {
            return null;
        }

        return $this->parsePlacesNewResult($result);
    }

    /** @return array<string, mixed>|null */
    public function reverseGeocode(float $latitude, float $longitude, string $language = 'az'): ?array
    {
        if (! $this->isConfigured()) {
            return [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'formatted_address' => null,
                'hints' => [],
            ];
        }

        $response = Http::timeout(12)->get(
            'https://maps.googleapis.com/maps/api/geocode/json',
            [
                'latlng' => "{$latitude},{$longitude}",
                'key' => $this->apiKey(),
                'language' => $language,
            ],
        );

        if (! $response->successful()) {
            return [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'formatted_address' => null,
                'hints' => [],
            ];
        }

        $results = $response->json('results', []);
        if (! is_array($results) || $results === []) {
            return [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'formatted_address' => null,
                'hints' => [],
            ];
        }

        $parsed = $this->parseGoogleResult($results[0]);
        if ($parsed === null) {
            return [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'formatted_address' => null,
                'hints' => [],
            ];
        }

        return [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'formatted_address' => $parsed['formatted_address'] ?? null,
            'hints' => $parsed['hints'] ?? [],
            'city' => $parsed['city'] ?? null,
            'district' => $parsed['district'] ?? null,
        ];
    }

    /**
     * Forward-geocode a place name (e.g. "Nərimanov, Bakı") inside Azerbaijan.
     *
     * @return array{latitude: float, longitude: float, formatted_address: ?string, city: ?string, district: ?string}|null
     */
    public function geocode(string $query, string $language = 'az'): ?array
    {
        $query = trim($query);
        if (! $this->isConfigured() || mb_strlen($query) < 2) {
            return null;
        }

        $response = Http::timeout(12)->get(
            'https://maps.googleapis.com/maps/api/geocode/json',
            [
                'address' => $query,
                'key' => $this->apiKey(),
                'language' => $language,
                'components' => 'country:AZ',
                'region' => 'az',
            ],
        );

        if (! $response->successful()) {
            return null;
        }

        $status = $response->json('status');
        if ($status !== 'OK') {
            return null;
        }

        $results = $response->json('results', []);
        if (! is_array($results) || $results === []) {
            return null;
        }

        $parsed = $this->parseGoogleResult($results[0]);
        if ($parsed === null) {
            return null;
        }

        $lat = (float) ($parsed['latitude'] ?? 0);
        $lng = (float) ($parsed['longitude'] ?? 0);
        if ($lat === 0.0 && $lng === 0.0) {
            return null;
        }

        return [
            'latitude' => $lat,
            'longitude' => $lng,
            'formatted_address' => isset($parsed['formatted_address'])
                ? (string) $parsed['formatted_address']
                : null,
            'city' => $parsed['city'] ?? null,
            'district' => $parsed['district'] ?? null,
        ];
    }

    /**
     * Places API (New) Place resource.
     *
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>|null
     */
    private function parsePlacesNewResult(array $result): ?array
    {
        $location = $result['location'] ?? null;
        if (! is_array($location)) {
            return null;
        }

        $lat = (float) ($location['latitude'] ?? 0);
        $lng = (float) ($location['longitude'] ?? 0);
        if ($lat === 0.0 && $lng === 0.0) {
            return null;
        }

        $hints = [];
        $city = null;
        $district = null;
        $components = $result['addressComponents'] ?? [];
        if (is_array($components)) {
            foreach ($components as $component) {
                if (! is_array($component)) {
                    continue;
                }
                $name = (string) ($component['longText'] ?? $component['shortText'] ?? '');
                if ($name === '') {
                    continue;
                }
                $hints[] = $name;
                $types = $component['types'] ?? [];
                if (! is_array($types)) {
                    continue;
                }
                if ($city === null && (
                    in_array('locality', $types, true)
                    || in_array('administrative_area_level_1', $types, true)
                )) {
                    $city = $this->normalizeAzAreaName($name);
                }
                if ($district === null && (
                    in_array('sublocality_level_1', $types, true)
                    || in_array('sublocality', $types, true)
                    || in_array('administrative_area_level_2', $types, true)
                    || in_array('neighborhood', $types, true)
                )) {
                    $district = $this->normalizeAzAreaName($name);
                }
            }
        }

        return [
            'latitude' => $lat,
            'longitude' => $lng,
            'formatted_address' => $result['formattedAddress'] ?? null,
            'hints' => $hints,
            'city' => $city,
            'district' => $district,
        ];
    }

    /**
     * Legacy Geocoding API result shape.
     *
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>|null
     */
    private function parseGoogleResult(array $result): ?array
    {
        $geometry = $result['geometry'] ?? null;
        $location = is_array($geometry) ? ($geometry['location'] ?? null) : null;
        if (! is_array($location)) {
            return null;
        }

        $hints = [];
        $city = null;
        $district = null;
        $components = $result['address_components'] ?? [];
        if (is_array($components)) {
            foreach ($components as $component) {
                if (! is_array($component)) {
                    continue;
                }
                $name = (string) ($component['long_name'] ?? '');
                if ($name === '') {
                    continue;
                }
                $hints[] = $name;
                $types = $component['types'] ?? [];
                if (! is_array($types)) {
                    continue;
                }
                if ($city === null && (
                    in_array('locality', $types, true)
                    || in_array('administrative_area_level_1', $types, true)
                )) {
                    $city = $this->normalizeAzAreaName($name);
                }
                if ($district === null && (
                    in_array('sublocality_level_1', $types, true)
                    || in_array('sublocality', $types, true)
                    || in_array('administrative_area_level_2', $types, true)
                    || in_array('neighborhood', $types, true)
                )) {
                    $district = $this->normalizeAzAreaName($name);
                }
            }
        }

        return [
            'latitude' => (float) ($location['lat'] ?? 0),
            'longitude' => (float) ($location['lng'] ?? 0),
            'formatted_address' => $result['formatted_address'] ?? null,
            'hints' => $hints,
            'city' => $city,
            'district' => $district,
        ];
    }

    private function normalizeAzAreaName(string $name): string
    {
        $name = trim($name);
        $name = preg_replace('/\s+(rayonu|rayon|şəhəri|seheri|city|district)\.?$/iu', '', $name) ?? $name;

        return trim($name);
    }

    private function apiKey(): string
    {
        return (string) config('homeservice.google_maps_api_key', '');
    }
}
