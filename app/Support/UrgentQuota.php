<?php

namespace App\Support;

use App\Models\ServiceRequest;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class UrgentQuota
{
    /**
     * @return array{
     *   daily_limit: int,
     *   daily_used: int,
     *   daily_remaining: int,
     *   radius_km: float,
     *   hours: int,
     *   fee: float,
     *   can_urgent: bool
     * }
     */
    public static function snapshot(User $user): array
    {
        $dailyLimit = (int) config('homeservice.urgent_daily_limit', 3);
        $today = Transaction::query()
            ->where('user_id', $user->id)
            ->where('type', 'urgent_fee')
            ->where('created_at', '>=', now()->startOfDay())
            ->count();
        $dailyRemaining = max(0, $dailyLimit - $today);

        return [
            'daily_limit' => $dailyLimit,
            'daily_used' => $today,
            'daily_remaining' => $dailyRemaining,
            'radius_km' => (float) config('homeservice.urgent_radius_km', 5),
            'hours' => (int) config('homeservice.urgent_hours', 2),
            'fee' => (float) config('homeservice.urgent_fee', 2),
            'can_urgent' => $dailyRemaining > 0,
        ];
    }

    public static function assertCanCharge(User $user, ?ServiceRequest $request = null): void
    {
        if ($request?->is_urgent && $request->urgent_until?->isFuture()) {
            throw ValidationException::withMessages([
                'urgent' => ['Təcili bildiriş hələ aktivdir.'],
            ]);
        }

        $snapshot = self::snapshot($user);
        if ($snapshot['can_urgent']) {
            return;
        }

        throw ValidationException::withMessages([
            'urgent' => ['Bu gün təcili limitiniz bitib. Sabah yenidən cəhd edin.'],
        ]);
    }
}
