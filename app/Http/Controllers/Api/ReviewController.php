<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\StoreReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Services\ReviewService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly ReviewService $reviews) {}

    public function index(Request $request): JsonResponse
    {
        return $this->success(
            ReviewResource::collection($this->reviews->receivedBy($request->user()))->resolve(),
            'Reviews'
        );
    }

    public function store(StoreReviewRequest $request, int $id): JsonResponse
    {
        $review = $this->reviews->create(
            $request->user(),
            $id,
            (int) $request->validated('rating'),
            $request->validated('comment'),
        );

        return $this->success(
            (new ReviewResource($review))->resolve(),
            'Rəy yazıldı',
            201
        );
    }
}
