<?php

namespace App\Http\Resources;

use App\Support\BumpQuota;
use App\Support\ConnectQuota;
use App\Support\ProfileCompleteness;
use App\Support\UrgentQuota;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\User */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'phone' => $this->phone,
            'name' => $this->name,
            'avatar_url' => $this->avatar_url,
            'active_role' => $this->active_role,
            'balance' => (float) $this->balance,
            'status' => $this->status,
            'welcome_bonus_granted' => $this->welcome_bonus_granted,
            'provider_profiles_count' => $this->provider_profiles_count
                ?? $this->providerProfiles()->count(),
            'profile_completeness' => ProfileCompleteness::forUser($this->resource),
            'connect_quota' => ConnectQuota::snapshot($this->resource),
            'urgent_quota' => UrgentQuota::snapshot($this->resource),
            'bump_quota' => BumpQuota::snapshot($this->resource),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
