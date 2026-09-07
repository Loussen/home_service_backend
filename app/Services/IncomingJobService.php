<?php

namespace App\Services;

use App\Models\RequestMatch;
use App\Models\User;
use App\Support\RequestTtl;
use Illuminate\Support\Collection;

class IncomingJobService
{
    public function listForProvider(User $user): Collection
    {
        abort_unless($user->isProvider(), 403, 'Bu əməliyyat yalnız xidmət göstərən üçündür');

        RequestTtl::expireOverdue();

        return RequestMatch::query()
            ->whereHas('providerProfile', fn ($q) => $q->where('user_id', $user->id))
            ->whereHas('serviceRequest', function ($q) {
                $q->whereNotIn('status', ['expired', 'cancelled', 'completed'])
                    ->where(function ($inner) {
                        $inner->whereNull('expires_at')
                            ->orWhere('expires_at', '>', now());
                    });
            })
            ->with([
                'providerProfile.category',
                'providerProfile.categories',
                'serviceRequest.category',
                'serviceRequest.user:id,name,phone',
            ])
            ->latest()
            ->limit(80)
            ->get();
    }
}
