<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\OpenConversationRequest;
use App\Http\Requests\Chat\ProviderReplyRequest;
use App\Http\Requests\Chat\StoreMessageRequest;
use App\Http\Requests\Chat\StoreOfferRequest;
use App\Http\Resources\ConversationResource;
use App\Http\Resources\MessageResource;
use App\Services\ConversationService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly ConversationService $conversations) {}

    public function index(Request $request): JsonResponse
    {
        $page = $this->conversations->listFor($request->user());

        return $this->success(
            ConversationResource::collection($page->items())->resolve(),
            'Conversations'
        );
    }

    public function store(OpenConversationRequest $request): JsonResponse
    {
        $conversation = $this->conversations->open(
            $request->user(),
            (int) $request->validated('provider_profile_id'),
            $request->validated('service_request_id') !== null
                ? (int) $request->validated('service_request_id')
                : null,
            $request->validated('message'),
        );

        return $this->success(
            (new ConversationResource($conversation))->resolve(),
            'Connected',
            201
        );
    }

    public function reply(ProviderReplyRequest $request): JsonResponse
    {
        $conversation = $this->conversations->replyAsProvider(
            $request->user(),
            (int) $request->validated('service_request_id'),
            $request->validated('provider_profile_id') !== null
                ? (int) $request->validated('provider_profile_id')
                : null,
            $request->validated('message'),
        );

        return $this->success(
            (new ConversationResource($conversation))->resolve(),
            'Cavab göndərildi',
            201
        );
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $conversation = $this->conversations->getFor($request->user(), $id);

        return $this->success((new ConversationResource($conversation))->resolve());
    }

    public function storeMessage(StoreMessageRequest $request, int $id): JsonResponse
    {
        $message = $this->conversations->send(
            $request->user(),
            $id,
            $request->validated('body'),
        );

        return $this->success(
            (new MessageResource($message->load('offer')))->resolve(),
            'Sent',
            201
        );
    }

    public function storeOffer(StoreOfferRequest $request, int $id): JsonResponse
    {
        $conversation = $this->conversations->createOffer(
            $request->user(),
            $id,
            $request->validated(),
        );

        return $this->success(
            (new ConversationResource($conversation))->resolve(),
            'Təklif göndərildi',
            201
        );
    }

    public function acceptOffer(Request $request, int $id): JsonResponse
    {
        $conversation = $this->conversations->respondOffer($request->user(), $id, true);

        return $this->success(
            (new ConversationResource($conversation))->resolve(),
            'Təklif qəbul edildi'
        );
    }

    public function declineOffer(Request $request, int $id): JsonResponse
    {
        $conversation = $this->conversations->respondOffer($request->user(), $id, false);

        return $this->success(
            (new ConversationResource($conversation))->resolve(),
            'Təklif rədd edildi'
        );
    }

    public function completeOffer(Request $request, int $id): JsonResponse
    {
        $conversation = $this->conversations->completeOffer($request->user(), $id);

        return $this->success(
            (new ConversationResource($conversation))->resolve(),
            'İş tamamlandı'
        );
    }

    public function cancelOffer(Request $request, int $id): JsonResponse
    {
        $conversation = $this->conversations->cancelOffer($request->user(), $id);

        return $this->success(
            (new ConversationResource($conversation))->resolve(),
            'Təklif ləğv edildi'
        );
    }
}
