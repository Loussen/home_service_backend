<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Repositories\CategoryRepository;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly CategoryRepository $categories) {}

    public function index(): JsonResponse
    {
        return $this->success(
            CategoryResource::collection($this->categories->tree())
        );
    }
}
