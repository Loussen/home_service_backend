<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\DeviceTokenController;
use App\Http\Controllers\Api\ProviderProfileController;
use App\Http\Controllers\Api\ServiceRequestController;
use App\Http\Controllers\Api\WalletController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/health', fn () => response()->json([
        'success' => true,
        'message' => 'OK',
        'data' => ['service' => 'home-service-api', 'version' => 'v1'],
    ]));

    Route::get('/categories', [CategoryController::class, 'index']);

    Route::prefix('auth')->group(function () {
        Route::post('/otp/send', [AuthController::class, 'sendOtp']);
        Route::post('/otp/verify', [AuthController::class, 'verifyOtp']);
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::prefix('auth')->group(function () {
            Route::get('/me', [AuthController::class, 'me']);
            Route::post('/role', [AuthController::class, 'setRole']);
            Route::patch('/profile', [AuthController::class, 'updateProfile']);
            Route::post('/logout', [AuthController::class, 'logout']);
        });

        Route::apiResource('provider-profiles', ProviderProfileController::class)
            ->only(['index', 'store', 'show', 'update', 'destroy']);
        Route::post('provider-profiles/{id}/audio-intro', [ProviderProfileController::class, 'uploadAudio']);
        Route::post('provider-profiles/{id}/bump', [ProviderProfileController::class, 'bump']);
        Route::post('provider-profiles/{id}/vip', [ProviderProfileController::class, 'vip']);

        Route::get('service-requests', [ServiceRequestController::class, 'index']);
        Route::post('service-requests/audio', [ServiceRequestController::class, 'storeAudio']);
        Route::post('service-requests/text', [ServiceRequestController::class, 'storeText']);
        Route::get('service-requests/{id}', [ServiceRequestController::class, 'show']);
        Route::post('service-requests/{id}/bump', [ServiceRequestController::class, 'bump']);
        Route::post('service-requests/{id}/urgent', [ServiceRequestController::class, 'urgent']);

        Route::get('wallet', [WalletController::class, 'show']);
        Route::get('wallet/transactions', [WalletController::class, 'transactions']);
        Route::post('wallet/top-up', [WalletController::class, 'topUp']);

        Route::post('device-tokens', [DeviceTokenController::class, 'store']);
        Route::delete('device-tokens', [DeviceTokenController::class, 'destroy']);
    });
});
