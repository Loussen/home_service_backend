<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BootstrapService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BootstrapController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly BootstrapService $bootstrap) {}

    public function show(Request $request): JsonResponse
    {
        $locale = $request->query('locale')
            ?? $request->header('Accept-Language');

        return $this->success($this->bootstrap->payload($locale));
    }
}
