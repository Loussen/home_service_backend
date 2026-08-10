<?php

namespace App\Services;

use App\Models\ProviderProfile;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Repositories\TransactionRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WalletService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly TransactionRepository $transactionRepository,
    ) {}

    public function grantWelcomeBonus(User $user): void
    {
        if ($user->welcome_bonus_granted) {
            return;
        }

        $amount = (float) config('homeservice.welcome_bonus', 10);

        DB::transaction(function () use ($user, $amount) {
            $this->userRepository->creditBalance($user, $amount);
            $this->userRepository->update($user, ['welcome_bonus_granted' => true]);
            $this->transactionRepository->create([
                'user_id' => $user->id,
                'amount' => $amount,
                'type' => 'credit_welcome_bonus',
                'payment_method' => 'system_bonus',
                'status' => 'completed',
                'reference' => 'welcome_'.$user->id,
            ]);
        });
    }

    public function debit(
        User $user,
        float $amount,
        string $type,
        string $paymentMethod = 'wallet',
        ?array $meta = null
    ): User {
        if ((float) $user->balance < $amount) {
            throw ValidationException::withMessages([
                'balance' => ['Insufficient balance.'],
            ]);
        }

        return DB::transaction(function () use ($user, $amount, $type, $paymentMethod, $meta) {
            $fresh = $this->userRepository->debitBalance($user, $amount);
            $this->transactionRepository->create([
                'user_id' => $user->id,
                'amount' => -$amount,
                'type' => $type,
                'payment_method' => $paymentMethod,
                'status' => 'completed',
                'meta' => $meta,
            ]);

            return $fresh;
        });
    }

    public function topUp(User $user, float $amount, string $method = 'card', ?string $reference = null): User
    {
        return DB::transaction(function () use ($user, $amount, $method, $reference) {
            $fresh = $this->userRepository->creditBalance($user, $amount);
            $this->transactionRepository->create([
                'user_id' => $user->id,
                'amount' => $amount,
                'type' => 'top_up',
                'payment_method' => $method,
                'status' => 'completed',
                'reference' => $reference,
            ]);

            return $fresh;
        });
    }

    public function chargeBumpUp(User $user, ProviderProfile|ServiceRequest $target): User
    {
        $fee = (float) config('homeservice.bump_up_fee', 1);
        $user = $this->debit($user, $fee, 'bump_up_fee', 'wallet', [
            'target_type' => $target::class,
            'target_id' => $target->id,
        ]);
        $target->update(['bumped_at' => now()]);

        return $user;
    }

    public function chargeUrgent(User $user, ServiceRequest $request): User
    {
        $fee = (float) config('homeservice.urgent_fee', 2);
        $user = $this->debit($user, $fee, 'urgent_fee', 'wallet', [
            'service_request_id' => $request->id,
        ]);
        $request->update([
            'is_urgent' => true,
            'urgent_until' => now()->addHours(2),
        ]);

        return $user;
    }

    public function chargeVip(User $user, ProviderProfile $profile): User
    {
        $fee = (float) config('homeservice.vip_fee', 15);
        $days = (int) config('homeservice.vip_days', 30);
        $user = $this->debit($user, $fee, 'vip_fee', 'wallet', [
            'provider_profile_id' => $profile->id,
        ]);
        $profile->update([
            'is_vip' => true,
            'vip_expires_at' => now()->addDays($days),
        ]);

        return $user;
    }

    public function history(User $user)
    {
        return $this->transactionRepository->listForUser($user);
    }
}
