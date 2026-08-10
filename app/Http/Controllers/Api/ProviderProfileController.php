<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\StoreProviderProfileRequest;
use App\Http\Requests\Profile\UpdateProviderProfileRequest;
use App\Http\Requests\Profile\UploadAudioIntroRequest;
use App\Http\Resources\ProviderProfileResource;
use App\Services\ProviderProfileService;
use App\Services\WalletService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProviderProfileController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly ProviderProfileService $profiles,
        private readonly WalletService $wallet,
    ) {}

    public function index(Request $request): JsonResponse
    {
        return $this->success(
            ProviderProfileResource::collection($this->profiles->list($request->user()))
        );
    }

    public function store(StoreProviderProfileRequest $request): JsonResponse
    {
        $profile = $this->profiles->create($request->user(), $request->validated());

        return $this->success(new ProviderProfileResource($profile), 'Profile created', 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $profile = $this->profiles->get($request->user(), $id);

        return $this->success(new ProviderProfileResource($profile));
    }

    public function update(UpdateProviderProfileRequest $request, int $id): JsonResponse
    {
        $profile = $this->profiles->update($request->user(), $id, $request->validated());

        return $this->success(new ProviderProfileResource($profile), 'Profile updated');
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->profiles->destroy($request->user(), $id);

        return $this->success(null, 'Profile deleted');
    }

    public function uploadAudio(UploadAudioIntroRequest $request, int $id): JsonResponse
    {
        $profile = $this->profiles->uploadAudioIntro(
            $request->user(),
            $id,
            $request->file('audio')
        );

        return $this->success(new ProviderProfileResource($profile), 'Audio intro saved');
    }

    public function bump(Request $request, int $id): JsonResponse
    {
        $profile = $this->profiles->get($request->user(), $id);
        $user = $this->wallet->chargeBumpUp($request->user(), $profile);

        return $this->success([
            'profile' => new ProviderProfileResource($profile->fresh()),
            'balance' => $user->balance,
        ], 'Profile bumped');
    }

    public function vip(Request $request, int $id): JsonResponse
    {
        $profile = $this->profiles->get($request->user(), $id);
        $user = $this->wallet->chargeVip($request->user(), $profile);

        return $this->success([
            'profile' => new ProviderProfileResource($profile->fresh()),
            'balance' => $user->balance,
        ], 'VIP activated');
    }
}
