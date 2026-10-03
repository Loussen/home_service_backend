<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Device\StoreDeviceTokenRequest;
use App\Models\DeviceToken;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
    use ApiResponse;

    public function store(StoreDeviceTokenRequest $request): JsonResponse
    {
        $locale = $request->validated('locale');
        $token = DeviceToken::updateOrCreate(
            ['token' => $request->validated('token')],
            [
                'user_id' => $request->user()->id,
                'platform' => $request->validated('platform'),
                'locale' => is_string($locale) && $locale !== ''
                    ? strtolower(substr($locale, 0, 8))
                    : null,
            ]
        );

        return $this->success($token, 'Device registered');
    }

    public function destroy(Request $request): JsonResponse
    {
        DeviceToken::where('user_id', $request->user()->id)
            ->where('token', $request->input('token'))
            ->delete();

        return $this->success(null, 'Device removed');
    }
}
