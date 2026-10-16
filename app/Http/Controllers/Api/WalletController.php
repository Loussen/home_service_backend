<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Wallet\ConfirmIapRequest;
use App\Http\Requests\Wallet\TopUpRequest;
use App\Http\Resources\TransactionResource;
use App\Http\Resources\UserResource;
use App\Services\IapService;
use App\Services\WalletService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly WalletService $wallet,
        private readonly IapService $iap,
    ) {}

    public function show(Request $request): JsonResponse
    {
        return $this->success([
            'balance' => $request->user()->balance,
            'currency' => 'AZN',
            'user' => new UserResource($request->user()),
            'iap_products' => $this->iap->products(),
        ]);
    }

    public function transactions(Request $request): JsonResponse
    {
        return $this->success(
            TransactionResource::collection($this->wallet->history($request->user()))
        );
    }

    public function products(): JsonResponse
    {
        return $this->success([
            'products' => $this->iap->products(),
        ]);
    }

    public function confirmIap(ConfirmIapRequest $request): JsonResponse
    {
        $data = $request->validated();
        $result = $this->iap->confirm(
            $request->user(),
            (string) $data['platform'],
            (string) $data['product_id'],
            (string) $data['verification_data'],
            isset($data['purchase_id']) ? (string) $data['purchase_id'] : null,
        );

        return $this->success([
            'balance' => $result['balance'],
            'credit' => $result['credit'],
            'already_credited' => $result['already_credited'],
            'user' => new UserResource($result['user']),
        ], $result['already_credited'] ? 'Already credited' : 'Wallet credited');
    }

    public function topUp(TopUpRequest $request): JsonResponse
    {
        // Dev/admin only — real top-up is StoreKit / Play Billing via confirmIap.
        abort_unless(
            app()->environment(['local', 'testing']) || (bool) config('homeservice.iap_allow_unverified'),
            403,
            'Use in-app purchase packages.',
        );

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
