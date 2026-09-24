<?php

namespace App\Services;

use App\Models\RequestMatch;
use App\Models\User;
use App\Support\RequestTtl;
use Illuminate\Support\Collection;

class IncomingJobService
{
    public function __construct(private readonly ModerationService $moderation) {}

    public function listForProvider(User $user): Collection
    {
        abort_unless($user->isProvider(), 403, 'Bu əməliyyat yalnız xidmət göstərən üçündür');

        RequestTtl::expireOverdue();

        $hiddenIds = $this->moderation->hiddenUserIdsFor($user)
            ->map(fn ($id) => (int) $id)
            ->all();

        return RequestMatch::query()
            ->whereHas('providerProfile', fn ($q) => $q->where('user_id', $user->id))
            ->whereHas('serviceRequest', function ($q) use ($hiddenIds) {
                // Show live + expired (history). Hide cancelled/completed only.
                $q->whereNotIn('status', ['cancelled', 'completed'])
                    ->when($hiddenIds !== [], fn ($q) => $q->whereNotIn('user_id', $hiddenIds));
            })
            ->with([
                'providerProfile.category',
                'providerProfile.categories',
                'serviceRequest.category',
                'serviceRequest.user:id,name,phone',
            ])
            ->latest()
            ->limit(80)
            ->get()
            ->sort(function (RequestMatch $a, RequestMatch $b) {
                $liveA = $this->isLiveMatch($a);
                $liveB = $this->isLiveMatch($b);
                if ($liveA !== $liveB) {
                    return $liveA ? -1 : 1;
                }

                return $b->id <=> $a->id;
            })
            ->values();
    }

    private function isLiveMatch(RequestMatch $match): bool
    {
        $sr = $match->serviceRequest;
        if (! $sr) {
            return false;
        }
        if (in_array($sr->status, ['expired', 'cancelled', 'completed'], true)) {
            return false;
        }

        return $sr->expires_at === null || $sr->expires_at->isFuture();
    }
}
