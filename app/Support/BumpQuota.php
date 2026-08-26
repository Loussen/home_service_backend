<?php

namespace App\Support;

use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use DateTimeInterface;

class BumpQuota
{
    public static function hours(): int
    {
        return (int) config('homeservice.bump_hours', 24);
    }

    public static function boostKm(): float
    {
        return (float) config('homeservice.bump_boost_km', 8);
    }

    public static function isActive(?DateTimeInterface $bumpedAt): bool
    {
        if ($bumpedAt === null) {
            return false;
        }

        return Carbon::instance($bumpedAt)->gt(now()->subHours(self::hours()));
    }

    public static function expiresAt(?DateTimeInterface $bumpedAt): ?Carbon
    {
        if ($bumpedAt === null) {
            return null;
        }

        return Carbon::instance($bumpedAt)->addHours(self::hours());
    }

    public static function remainingHours(?DateTimeInterface $bumpedAt): int
    {
        $expires = self::expiresAt($bumpedAt);
        if ($expires === null || $expires->isPast()) {
            return 0;
        }

        $seconds = max(0, $expires->getTimestamp() - now()->getTimestamp());

        return max(1, (int) ceil($seconds / 3600));
    }

    /**
     * @return array{daily_limit: int, daily_used: int, daily_remaining: int, hours: int, fee: float, can_bump: bool}
     */
    public static function snapshot(User $user): array
    {
        $dailyLimit = (int) config('homeservice.bump_daily_limit', 2);
        $today = Transaction::query()
            ->where('user_id', $user->id)
            ->where('type', 'bump_up_fee')
            ->where('created_at', '>=', now()->startOfDay())
            ->count();
        $dailyRemaining = max(0, $dailyLimit - $today);

        return [
            'daily_limit' => $dailyLimit,
            'daily_used' => $today,
            'daily_remaining' => $dailyRemaining,
            'hours' => self::hours(),
            'fee' => (float) config('homeservice.bump_up_fee', 1),
            'can_bump' => $dailyRemaining > 0,
        ];
    }
}
