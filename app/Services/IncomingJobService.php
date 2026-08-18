<?php

namespace App\Services;

use App\Models\RequestMatch;
use App\Models\User;
use Illuminate\Support\Collection;

class IncomingJobService
{
    public function listForProvider(User $user): Collection
    {
        abort_unless($user->isProvider(), 403, 'Bu əməliyyat yalnız xidmət göstərən üçündür');

        return RequestMatch::query()
            ->whereHas('providerProfile', fn ($q) => $q->where('user_id', $user->id))
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
