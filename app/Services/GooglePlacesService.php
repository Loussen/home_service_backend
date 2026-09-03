<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GooglePlacesService
{
    private const DEFAULT_CENTER = '40.4093,49.8671';

    private const DEFAULT_RADIUS = 250000;

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

        $response = Http::timeout(12)->get(
            'https://maps.googleapis.com/maps/api/place/autocomplete/json',
            [
                'input' => trim($query),
                'key' => $this->apiKey(),
                'language' => $language,
                'components' => 'country:az',
                'location' => self::DEFAULT_CENTER,
                'radius' => self::DEFAULT_RADIUS,
            ],
        );

        if (! $response->successful()) {
            return [];
        }

        $status = $response->json('status');
        if (! in_array($status, ['OK', 'ZERO_RESULTS'], true)) {
            return [];
        }

        $predictions = $response->json('predictions', []);
        $items = [];

        foreach ($predictions as $prediction) {
            if (! is_array($prediction)) {
                continue;
            }
            $placeId = (string) ($prediction['place_id'] ?? '');
            if ($placeId === '') {
                continue;
            }
            $items[] = [
                'place_id' => $placeId,
                'description' => (string) ($prediction['description'] ?? ''),
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

        $response = Http::timeout(12)->get(
            'https://maps.googleapis.com/maps/api/place/details/json',
            [
                'place_id' => $placeId,
                'key' => $this->apiKey(),
                'language' => $language,
                'fields' => 'geometry,address_component,formatted_address',
            ],
        );

        if (! $response->successful()) {
            return null;
        }

        $result = $response->json('result');
        if (! is_array($result)) {
            return null;
        }

        return $this->parseGoogleResult($result);
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

    /** @param  array<string, mixed>  $result
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
                // City: Bakı / Gəncə …
                if ($city === null && (
                    in_array('locality', $types, true)
                    || in_array('administrative_area_level_1', $types, true)
                )) {
                    $city = $this->normalizeAzAreaName($name);
                }
                // District / rayon
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
