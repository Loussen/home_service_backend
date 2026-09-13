<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProviderProfileResource;
use App\Services\FavoriteService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly FavoriteService $favorites) {}

    public function index(Request $request): JsonResponse
    {
        $profiles = $this->favorites->listFor($request->user());

        return $this->success(
            ProviderProfileResource::collection($profiles),
            'Favorites'
        );
    }

    public function store(Request $request, int $id): JsonResponse
    {
        $profile = $this->favorites->add($request->user(), $id);

        return $this->success(
            new ProviderProfileResource($profile),
            'Seçilmişlərə əlavə olundu',
            201
        );
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->favorites->remove($request->user(), $id);

        return $this->success(null, 'Seçilmişlərdən çıxarıldı');
    }
}
