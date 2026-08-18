<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserBlock;
use App\Models\UserReport;
use Illuminate\Support\Collection;

class ModerationService
{
    /** @var list<string> */
    public const REPORT_REASONS = [
        'spam',
        'harassment',
        'fraud',
        'inappropriate',
        'other',
    ];

    public function block(User $user, int $targetUserId): void
    {
        abort_if($targetUserId === $user->id, 422, 'Özünüzü blok edə bilməzsiniz');

        $target = User::query()->find($targetUserId);
        abort_if(! $target, 404, 'İstifadəçi tapılmadı');

        UserBlock::query()->firstOrCreate([
            'blocker_id' => $user->id,
            'blocked_id' => $targetUserId,
        ]);
    }

    public function unblock(User $user, int $targetUserId): void
    {
        UserBlock::query()
            ->where('blocker_id', $user->id)
            ->where('blocked_id', $targetUserId)
            ->delete();
    }

    public function blockedIdsFor(User $user): Collection
    {
        return UserBlock::query()
            ->where('blocker_id', $user->id)
            ->pluck('blocked_id');
    }

    public function hiddenUserIdsFor(User $user): Collection
    {
        $blockedByMe = UserBlock::query()
            ->where('blocker_id', $user->id)
            ->pluck('blocked_id');
        $blockedMe = UserBlock::query()
            ->where('blocked_id', $user->id)
            ->pluck('blocker_id');

        return $blockedByMe->merge($blockedMe)->unique()->values();
    }

    public function isBlockedEitherWay(User $a, User $b): bool
    {
        return UserBlock::query()
            ->where(function ($q) use ($a, $b) {
                $q->where('blocker_id', $a->id)->where('blocked_id', $b->id);
            })
            ->orWhere(function ($q) use ($a, $b) {
                $q->where('blocker_id', $b->id)->where('blocked_id', $a->id);
            })
            ->exists();
    }

    public function assertNotBlocked(User $actor, User $target): void
    {
        abort_if($this->isBlockedEitherWay($actor, $target), 403, 'Bu istifadəçi ilə əlaqə bloklanıb');
    }

    public function report(
        User $user,
        int $reportedUserId,
        string $reason,
        ?string $details = null,
        ?int $conversationId = null,
    ): UserReport {
        abort_if($reportedUserId === $user->id, 422, 'Özünüzü şikayət edə bilməzsiniz');
        abort_if(! in_array($reason, self::REPORT_REASONS, true), 422, 'Səbəb düzgün deyil');

        $target = User::query()->find($reportedUserId);
        abort_if(! $target, 404, 'İstifadəçi tapılmadı');

        if ($conversationId) {
            $allowed = \App\Models\Conversation::query()
                ->whereKey($conversationId)
                ->where(function ($q) use ($user) {
                    $q->where('client_id', $user->id)
                        ->orWhere('provider_id', $user->id);
                })
                ->exists();
            abort_unless($allowed, 404, 'Söhbət tapılmadı');
        }

        return UserReport::query()->create([
            'reporter_id' => $user->id,
            'reported_user_id' => $reportedUserId,
            'conversation_id' => $conversationId,
            'reason' => $reason,
            'details' => $details,
            'status' => UserReport::PENDING,
        ]);
    }
}
