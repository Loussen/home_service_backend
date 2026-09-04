<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ServiceRequest\StoreAudioRequest;
use App\Http\Requests\ServiceRequest\StoreTextRequest;
use App\Http\Resources\ServiceRequestResource;
use App\Services\PushNotificationService;
use App\Services\ServiceRequestService;
use App\Services\WalletService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceRequestController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly ServiceRequestService $serviceRequests,
        private readonly WalletService $wallet,
        private readonly PushNotificationService $push,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $paginator = $this->serviceRequests->list(
            $request->user(),
            max(1, (int) $request->query('page', 1)),
            max(1, min(50, (int) $request->query('per_page', 10))),
            (string) $request->query('filter', 'all'),
        );

        return $this->success([
            'items' => ServiceRequestResource::collection($paginator->getCollection())->resolve(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function storeAudio(StoreAudioRequest $request): JsonResponse
    {
        $serviceRequest = $this->serviceRequests->createFromAudio(
            $request->user(),
            $request->file('audio'),
            (float) $request->validated('latitude'),
            (float) $request->validated('longitude'),
            $request->validated('address'),
            (bool) $request->boolean('is_urgent'),
            $request->validated('category_id') !== null
                ? (int) $request->validated('category_id')
                : null,
            $request->validated('scheduled_at'),
            $request->validated('time_slot'),
            $request->validated('child_age'),
            $request->validated('has_pet'),
            $request->validated('budget_max') !== null
                ? (float) $request->validated('budget_max')
                : null,
        );

        return $this->success(
            new ServiceRequestResource($serviceRequest),
            'Request accepted for processing',
            202
        );
    }

    public function storeText(StoreTextRequest $request): JsonResponse
    {
        $serviceRequest = $this->serviceRequests->createText(
            $request->user(),
            $request->validated('text'),
            (float) $request->validated('latitude'),
            (float) $request->validated('longitude'),
            $request->validated('category_id'),
            $request->validated('address'),
            (bool) $request->boolean('is_urgent'),
            $request->validated('scheduled_at'),
            $request->validated('time_slot'),
            $request->validated('child_age'),
            $request->validated('has_pet'),
            $request->validated('budget_max') !== null
                ? (float) $request->validated('budget_max')
                : null,
        );

        return $this->success(new ServiceRequestResource($serviceRequest), 'Request created', 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $serviceRequest = $this->serviceRequests->get($request->user(), $id);

        return $this->success(new ServiceRequestResource($serviceRequest));
    }

    public function bump(Request $request, int $id): JsonResponse
    {
        $serviceRequest = $this->serviceRequests->get($request->user(), $id);
        $user = $this->wallet->chargeBumpUp($request->user(), $serviceRequest);

        return $this->success([
            'request' => new ServiceRequestResource($serviceRequest->fresh()),
            'balance' => $user->balance,
        ], 'Request bumped');
    }

    public function urgent(Request $request, int $id): JsonResponse
    {
        $serviceRequest = $this->serviceRequests->get($request->user(), $id);
        $user = $this->wallet->chargeUrgent($request->user(), $serviceRequest);
        $serviceRequest = $serviceRequest->fresh() ?? $serviceRequest;
        $this->push->notifyNewMatches($serviceRequest, force: true);

        return $this->success([
            'request' => new ServiceRequestResource($serviceRequest->fresh()),
            'balance' => $user->balance,
        ], 'Urgent push activated');
    }
}
