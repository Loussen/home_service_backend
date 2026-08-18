<?php

namespace App\Http\Resources;

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
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
