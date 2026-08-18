<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\IncomingJobResource;
use App\Services\IncomingJobService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IncomingJobController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly IncomingJobService $jobs) {}

    public function index(Request $request): JsonResponse
    {
        $jobs = $this->jobs->listForProvider($request->user());

        return $this->success(
            IncomingJobResource::collection($jobs)->resolve(),
            'Jobs'
        );
    }
}
