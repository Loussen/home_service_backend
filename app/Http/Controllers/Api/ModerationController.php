<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Moderation\StoreReportRequest;
use App\Http\Resources\UserReportResource;
use App\Services\ModerationService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ModerationController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly ModerationService $moderation) {}

    public function block(Request $request, int $id): JsonResponse
    {
        $this->moderation->block($request->user(), $id);

        return $this->success(null, 'İstifadəçi bloklandı');
    }

    public function unblock(Request $request, int $id): JsonResponse
    {
        $this->moderation->unblock($request->user(), $id);

        return $this->success(null, 'Blok götürüldü');
    }

    public function blocks(Request $request): JsonResponse
    {
        return $this->success(
            $this->moderation->blockedIdsFor($request->user())->values()->all(),
            'Blocked users'
        );
    }

    public function report(StoreReportRequest $request): JsonResponse
    {
        $report = $this->moderation->report(
            $request->user(),
            (int) $request->validated('reported_user_id'),
            $request->validated('reason'),
            $request->validated('details'),
            $request->validated('conversation_id') !== null
                ? (int) $request->validated('conversation_id')
                : null,
        );

        return $this->success(
            (new UserReportResource($report))->resolve(),
            'Şikayət göndərildi',
            201
        );
    }
}
