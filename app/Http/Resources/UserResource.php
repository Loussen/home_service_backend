<?php

namespace App\Http\Resources;

use App\Support\BumpQuota;
use App\Support\ConnectQuota;
use App\Support\ProfileCompleteness;
use App\Support\UrgentQuota;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin \App\Models\User */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $avatar = $this->avatar_url;
        if ($avatar && ! str_starts_with($avatar, 'http')) {
            $avatar = Storage::disk('public')->url($avatar);
        }

        return [
            'id' => $this->id,
            'phone' => $this->phone,
            'name' => $this->name,
            'avatar_url' => $avatar,
            'active_role' => $this->active_role,
            'needs_role' => $this->needsRole(),
            'provider_approval_status' => $this->provider_approval_status,
            'needs_provider_approval' => $this->needsProviderApproval(),
            'provider_rejection_note' => $this->when(
                $this->provider_approval_status === 'rejected',
                $this->provider_rejection_note
            ),
            'provider_approval_message' => $this->when(
                $this->needsProviderApproval(),
                $this->provider_approval_status === 'rejected'
                    ? 'Hesabınız rədd edilib. Dəstəklə əlaqə saxlayın.'
                    : 'Sorğunuz 1 saat ərzində baxılacaq. Təsdiqləndikdən sonra iş sorğuları gələcək.'
            ),
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
