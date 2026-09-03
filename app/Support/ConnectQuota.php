<?php

namespace App\Support;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ConnectQuota
{
    /**
     * @return array{
     *   in_free_window: bool,
     *   free_quota: int,
     *   free_used: int,
     *   daily_limit: int,
     *   daily_used: int,
     *   daily_remaining: int,
     *   fee: float,
     *   can_connect: bool
     * }
     */
    public static function snapshot(User $user): array
    {
        $freeQuota = (int) config('homeservice.connect_free_quota', 5);
        $freeDays = (int) config('homeservice.connect_free_days', 30);
        $dailyLimit = (int) config('homeservice.connect_daily_limit', 10);
        $fee = (float) config('homeservice.connect_fee', 0.5);

        $lifetime = Conversation::query()->where('client_id', $user->id)->count();
        $today = Conversation::query()
            ->where('client_id', $user->id)
            ->where('created_at', '>=', now()->startOfDay())
            ->count();

        $inFreeWindow = $lifetime < $freeQuota
            || ($user->created_at !== null && $user->created_at->gte(now()->subDays($freeDays)));

        $dailyRemaining = max(0, $dailyLimit - $today);
        $effectiveFee = $inFreeWindow ? 0.0 : $fee;
        $canAfford = $effectiveFee <= 0 || (float) $user->balance >= $effectiveFee;

        return [
            'in_free_window' => $inFreeWindow,
            'free_quota' => $freeQuota,
            'free_used' => min($lifetime, $freeQuota),
            'free_remaining' => max(0, $freeQuota - $lifetime),
            'daily_limit' => $dailyLimit,
            'daily_used' => $today,
            'daily_remaining' => $dailyRemaining,
            'fee' => $effectiveFee,
            'can_connect' => $dailyRemaining > 0 && $canAfford,
        ];
    }

    /**
     * @param  array<string, mixed>  $snapshot
     */
    public static function assertCanOpen(array $snapshot): void
    {
        if (($snapshot['daily_remaining'] ?? 0) <= 0) {
            throw ValidationException::withMessages([
                'connect' => ['Bu gün CONNECT limitiniz bitib. Sabah yenidən cəhd edin.'],
            ]);
        }

        if ((float) ($snapshot['fee'] ?? 0) > 0 && ! ($snapshot['can_connect'] ?? false)) {
            throw ValidationException::withMessages([
                'balance' => ['Balansınız kifayət etmir. CONNECT üçün balans artırın.'],
            ]);
        }

        if ($snapshot['can_connect'] ?? false) {
            return;
        }

        throw ValidationException::withMessages([
            'connect' => ['CONNECT mümkün deyil.'],
        ]);
    }
}
