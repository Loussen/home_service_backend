<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\AppStringRepository;
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

        return $this->success($details);
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
            $this->places->reverseGeocode($latitude, $longitude, $language)
        );
    }
}
