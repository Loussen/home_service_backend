<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SendOtpRequest;
use App\Http\Requests\Auth\SetRoleRequest;
use App\Http\Requests\Auth\UpdateUserRequest;
use App\Http\Requests\Auth\UploadAvatarRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly AuthService $authService) {}

    public function sendOtp(SendOtpRequest $request): JsonResponse
    {
        $data = $this->authService->sendOtp($request->validated('phone'));

        return $this->success($data, 'OTP sent');
    }

    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $data = $this->authService->verifyOtp(
            $request->validated('phone'),
            $request->validated('code')
        );

        return $this->success([
            'token' => $data['token'],
            'token_type' => $data['token_type'],
            'is_new_user' => $data['is_new_user'],
            'user' => new UserResource($data['user']),
        ], 'Authenticated');
    }

    public function me(Request $request): JsonResponse
    {
        return $this->success(new UserResource($request->user()));
    }

    public function setRole(SetRoleRequest $request): JsonResponse
    {
        $user = $this->authService->setRole(
            $request->user(),
            $request->validated('role')
        );

        return $this->success(new UserResource($user), 'Role updated');
    }

    public function updateProfile(UpdateUserRequest $request): JsonResponse
    {
        $user = $this->authService->updateProfile(
            $request->user(),
            $request->validated()
        );

        return $this->success(new UserResource($user), 'Profile updated');
    }

    public function uploadAvatar(UploadAvatarRequest $request): JsonResponse
    {
        $user = $this->authService->uploadAvatar(
            $request->user(),
            $request->file('avatar')
        );

        return $this->success(new UserResource($user), 'Avatar updated');
    }

    public function logout(Request $request): JsonResponse
    {
        $plain = $request->bearerToken();
        if ($plain) {
            PersonalAccessToken::findToken($plain)?->delete();
        }

        return $this->success(null, 'Logged out');
    }
}
