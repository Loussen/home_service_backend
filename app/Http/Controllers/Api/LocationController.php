<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CityResource;
use App\Repositories\LocationRepository;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class LocationController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly LocationRepository $locations) {}

    public function cities(): JsonResponse
    {
        return $this->success(
            CityResource::collection($this->locations->citiesWithDistricts())
        );
    }
}
