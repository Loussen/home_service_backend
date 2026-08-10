<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Wallet\TopUpRequest;
use App\Http\Resources\TransactionResource;
use App\Http\Resources\UserResource;
use App\Services\WalletService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly WalletService $wallet) {}

    public function show(Request $request): JsonResponse
    {
        return $this->success([
            'balance' => $request->user()->balance,
            'currency' => 'AZN',
            'user' => new UserResource($request->user()),
        ]);
    }

    public function transactions(Request $request): JsonResponse
    {
        return $this->success(
            TransactionResource::collection($this->wallet->history($request->user()))
        );
    }

    public function topUp(TopUpRequest $request): JsonResponse
    {
        // Placeholder: real card/terminal webhooks will replace this later
        $user = $this->wallet->topUp(
            $request->user(),
            (float) $request->validated('amount'),
            $request->validated('payment_method', 'card'),
            $request->validated('reference'),
        );

        return $this->success([
            'balance' => $user->balance,
            'user' => new UserResource($user),
        ], 'Balance topped up');
    }
}
