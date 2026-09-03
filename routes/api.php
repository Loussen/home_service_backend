<?php

use App\Http\Controllers\Api\BootstrapController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\DeviceTokenController;
use App\Http\Controllers\Api\IncomingJobController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\ModerationController;
use App\Http\Controllers\Api\PlacesController;
use App\Http\Controllers\Api\ProviderProfileController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\ServiceRequestController;
use App\Http\Controllers\Api\VerificationDocumentController;
use App\Http\Controllers\Api\WalletController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/health', fn () => response()->json([
        'success' => true,
        'message' => 'OK',
        'data' => ['service' => 'home-service-api', 'version' => 'v1'],
    ]));

    Route::get('/bootstrap', [BootstrapController::class, 'show']);

    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/cities', [LocationController::class, 'cities']);

    Route::prefix('auth')->group(function () {
        Route::post('/otp/send', [AuthController::class, 'sendOtp'])
            ->middleware('throttle:8,1');
        Route::post('/otp/verify', [AuthController::class, 'verifyOtp'])
            ->middleware('throttle:10,1');
        Route::post('/logout', [AuthController::class, 'logout']);
    });

    Route::middleware(['auth:sanctum', 'user.not_blocked'])->group(function () {
        Route::prefix('auth')->group(function () {
            Route::get('/me', [AuthController::class, 'me']);
            Route::post('/role', [AuthController::class, 'setRole']);
            Route::patch('/profile', [AuthController::class, 'updateProfile']);
            Route::post('/avatar', [AuthController::class, 'uploadAvatar']);
            Route::post('/provider/resubmit-review', [AuthController::class, 'resubmitProviderReview']);
        });

        Route::post('device-tokens', [DeviceTokenController::class, 'store']);
        Route::delete('device-tokens', [DeviceTokenController::class, 'destroy']);

        Route::get('places/autocomplete', [PlacesController::class, 'autocomplete']);
        Route::get('places/reverse', [PlacesController::class, 'reverse']);
        Route::get('places/{placeId}', [PlacesController::class, 'details']);

        Route::middleware('role.chosen')->group(function () {
        Route::get('providers/{id}', [ProviderProfileController::class, 'publicShow'])
            ->whereNumber('id');

        Route::middleware('role:provider')->group(function () {
            Route::apiResource('provider-profiles', ProviderProfileController::class)
                ->only(['index', 'store', 'show', 'update', 'destroy']);
            Route::post('provider-profiles/{id}/audio-intro', [ProviderProfileController::class, 'uploadAudio']);
            Route::post('provider-profiles/{id}/bump', [ProviderProfileController::class, 'bump'])
                ->middleware('provider.approved');
            Route::post('provider-profiles/{id}/vip', [ProviderProfileController::class, 'vip'])
                ->middleware('provider.approved');
            Route::get('verification-documents', [VerificationDocumentController::class, 'index']);
            Route::post('verification-documents', [VerificationDocumentController::class, 'store']);
            Route::get('jobs', [IncomingJobController::class, 'index'])
                ->middleware('provider.approved');
            Route::post('conversations/reply', [ConversationController::class, 'reply'])
                ->middleware('provider.approved');
        });

        Route::middleware('role:client')->group(function () {
            Route::get('service-requests', [ServiceRequestController::class, 'index']);
            Route::post('service-requests/audio', [ServiceRequestController::class, 'storeAudio']);
            Route::post('service-requests/text', [ServiceRequestController::class, 'storeText']);
            Route::get('service-requests/{id}', [ServiceRequestController::class, 'show']);
            Route::post('service-requests/{id}/bump', [ServiceRequestController::class, 'bump']);
            Route::post('service-requests/{id}/urgent', [ServiceRequestController::class, 'urgent']);
            Route::post('conversations', [ConversationController::class, 'store']);
        });

        Route::get('wallet', [WalletController::class, 'show']);
        Route::get('wallet/transactions', [WalletController::class, 'transactions']);
        Route::post('wallet/top-up', [WalletController::class, 'topUp']);

        Route::get('conversations', [ConversationController::class, 'index']);
        Route::get('conversations/{id}', [ConversationController::class, 'show']);
        Route::post('conversations/{id}/messages', [ConversationController::class, 'storeMessage']);
        Route::post('conversations/{id}/offers', [ConversationController::class, 'storeOffer']);
        Route::post('offers/{id}/accept', [ConversationController::class, 'acceptOffer']);
        Route::post('offers/{id}/decline', [ConversationController::class, 'declineOffer']);
        Route::post('offers/{id}/complete', [ConversationController::class, 'completeOffer']);
        Route::post('offers/{id}/cancel', [ConversationController::class, 'cancelOffer']);
        Route::post('offers/{id}/reviews', [ReviewController::class, 'store']);
        Route::get('reviews', [ReviewController::class, 'index']);

        Route::get('blocks', [ModerationController::class, 'blocks']);
        Route::post('users/{id}/block', [ModerationController::class, 'block']);
        Route::delete('users/{id}/block', [ModerationController::class, 'unblock']);
        Route::post('reports', [ModerationController::class, 'report']);

        Route::get('bookings', [BookingController::class, 'index']);
        Route::post('bookings/{id}/cancel', [BookingController::class, 'cancel']);
        });
    });
});
