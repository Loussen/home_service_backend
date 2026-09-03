<?php

namespace App\Http\Resources;

use App\Repositories\AppStringRepository;
use App\Support\BumpQuota;
use App\Support\ConnectQuota;
use App\Support\ProfileCompleteness;
use App\Support\RequestLocale;
use App\Support\UrgentQuota;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin \App\Models\User */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $strings = app(AppStringRepository::class)->forLocale(RequestLocale::from($request));
        $t = static function (string $key, string $fallback) use ($strings): string {
            $value = $strings[$key] ?? $fallback;

            return is_string($value) && $value !== '' ? $value : $fallback;
        };

        $avatar = $this->avatar_url;
        if ($avatar && ! str_starts_with($avatar, 'http')) {
            $avatar = Storage::disk('public')->url($avatar);
            if (! str_starts_with($avatar, 'http')) {
                $avatar = url($avatar);
            }
        }

        $approvalMessage = null;
        if ($this->isProvider() && $this->provider_approval_status) {
            $approvalMessage = match ($this->provider_approval_status) {
                'rejected' => filled($this->provider_rejection_note)
                    ? $t('provider.approval.rejected', 'Hesabınız rədd edilib. Dəstəklə əlaqə saxlayın.').' '.$this->provider_rejection_note
                    : $t('provider.approval.rejected', 'Hesabınız rədd edilib. Dəstəklə əlaqə saxlayın.'),
                'approved' => $t(
                    'provider.approval.approved',
                    'Hesabınız təsdiqləndi. İndi iş sorğuları gələ bilər.'
                ),
                default => $t(
                    'provider.approval.pending',
                    'Sorğunuz 1 saat ərzində baxılacaq. Təsdiqləndikdən sonra iş sorğuları gələcək.'
                ),
            };
        }

        $profileStatus = null;
        $profileStatusLabel = null;
        if ($this->isBlocked()) {
            $profileStatus = 'blocked';
            $profileStatusLabel = $t('web.status.blocked', 'Bloklanıb');
        } elseif ($this->isProvider()) {
            $profileStatus = $this->provider_approval_status ?: 'pending';
            $profileStatusLabel = match ($profileStatus) {
                'approved' => $t('web.status.approved', 'Təsdiqli'),
                'rejected' => $t('web.status.rejected', 'Rədd edilib'),
                default => $t('web.status.pending', 'Gözləyir'),
            };
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
            'provider_approval_message' => $approvalMessage,
            'balance' => (float) $this->balance,
            'status' => $this->status,
            'is_blocked' => $this->isBlocked(),
            'profile_status' => $profileStatus,
            'profile_status_label' => $profileStatusLabel,
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
