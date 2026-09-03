<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\AppStringRepository;
use App\Repositories\LocationRepository;
use App\Services\GooglePlacesService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlacesController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly GooglePlacesService $places,
        private readonly AppStringRepository $strings,
        private readonly LocationRepository $locations,
    ) {}

    public function autocomplete(Request $request): JsonResponse
    {
        if (! $this->places->isConfigured()) {
            return $this->error('Google Maps API key is not configured', 503);
        }

        $query = (string) $request->query('q', $request->query('input', ''));
        $language = $this->strings->normalize(
            (string) $request->query('language', $request->query('locale', 'az')),
        );

        return $this->success($this->places->autocomplete($query, $language));
    }

    public function details(Request $request, string $placeId): JsonResponse
    {
        if (! $this->places->isConfigured()) {
            return $this->error('Google Maps API key is not configured', 503);
        }

        $language = $this->strings->normalize(
            (string) $request->query('language', $request->query('locale', 'az')),
        );

        $details = $this->places->details($placeId, $language);
        if ($details === null) {
            return $this->error('Place not found', 404);
        }

        return $this->success($this->withResolvedLocation($details));
    }

    public function reverse(Request $request): JsonResponse
    {
        if (! $this->places->isConfigured()) {
            return $this->error('Google Maps API key is not configured', 503);
        }

        $latitude = (float) $request->query('lat', $request->query('latitude', 0));
        $longitude = (float) $request->query('lng', $request->query('longitude', 0));
        $language = $this->strings->normalize(
            (string) $request->query('language', $request->query('locale', 'az')),
        );

        return $this->success(
            $this->withResolvedLocation(
                $this->places->reverseGeocode($latitude, $longitude, $language) ?? []
            )
        );
    }

    /**
     * @param  array<string, mixed>  $place
     * @return array<string, mixed>
     */
    private function withResolvedLocation(array $place): array
    {
        $resolved = $this->locations->resolveNames(
            isset($place['city']) ? (string) $place['city'] : null,
            isset($place['district']) ? (string) $place['district'] : null,
        );

        // If Google city/district didn't match DB, try address component hints.
        if (! $resolved['city'] && ! empty($place['hints']) && is_array($place['hints'])) {
            foreach ($place['hints'] as $hint) {
                if (! is_string($hint) || $hint === '') {
                    continue;
                }
                $try = $this->locations->resolveNames($hint, $place['district'] ?? null);
                if ($try['city'] || $try['district']) {
                    $resolved = $try;
                    break;
                }
                $tryDistrict = $this->locations->resolveNames(null, $hint);
                if ($tryDistrict['district']) {
                    $resolved = $tryDistrict;
                    break;
                }
            }
        }

        $place['city'] = $resolved['city'] ?? ($place['city'] ?? null);
        $place['district'] = $resolved['district'] ?? ($place['district'] ?? null);
        $place['city_id'] = $resolved['city_id'];
        $place['district_id'] = $resolved['district_id'];

        return $place;
    }
}
