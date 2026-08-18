<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\StoreVerificationDocumentRequest;
use App\Http\Resources\VerificationDocumentResource;
use App\Services\VerificationDocumentService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VerificationDocumentController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly VerificationDocumentService $documents) {}

    public function index(Request $request): JsonResponse
    {
        return $this->success(
            VerificationDocumentResource::collection($this->documents->listFor($request->user()))->resolve(),
            'Verification documents'
        );
    }

    public function store(StoreVerificationDocumentRequest $request): JsonResponse
    {
        $document = $this->documents->upload(
            $request->user(),
            $request->file('document'),
            $request->validated('document_type') ?? 'id_card',
            $request->validated('provider_profile_id') !== null
                ? (int) $request->validated('provider_profile_id')
                : null,
        );

        return $this->success(
            (new VerificationDocumentResource($document))->resolve(),
            'Sənəd göndərildi — admin yoxlayacaq',
            201
        );
    }
}
