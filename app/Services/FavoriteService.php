<?php

namespace App\Services;

use App\Models\Favorite;
use App\Models\ProviderProfile;
use App\Models\User;
use Illuminate\Support\Collection;

class FavoriteService
{
    /** @var array<int, list<int>> */
    private array $idsCache = [];

    /**
     * @return Collection<int, ProviderProfile>
     */
    public function listFor(User $user): Collection
    {
        abort_unless($user->isClient(), 403, 'Seçilmişlər yalnız müştəri üçündür');

        return Favorite::query()
            ->where('user_id', $user->id)
            ->with([
                'providerProfile.user:id,name,phone,avatar_url,active_role,provider_approval_status',
                'providerProfile.category',
                'providerProfile.categories',
            ])
            ->orderByDesc('id')
            ->get()
            ->map(function (Favorite $fav) {
                $profile = $fav->providerProfile;
                if ($profile) {
                    $profile->setAttribute('is_favorite', true);
                }

                return $profile;
            })
            ->filter()
            ->values();
    }

    public function add(User $user, int $providerProfileId): ProviderProfile
    {
        abort_unless($user->isClient(), 403, 'Seçilmişlər yalnız müştəri üçündür');

        $profile = $this->resolvableProfile($providerProfileId);
        abort_if((int) $profile->user_id === (int) $user->id, 422, 'Öz profilinizi seçilmişə əlavə edə bilməzsiniz');

        Favorite::query()->firstOrCreate([
            'user_id' => $user->id,
            'provider_profile_id' => $profile->id,
        ]);

        unset($this->idsCache[$user->id]);
        $profile->setAttribute('is_favorite', true);

        return $profile->loadMissing([
            'user:id,name,phone,avatar_url,active_role,provider_approval_status',
            'category',
            'categories',
        ]);
    }

    public function remove(User $user, int $providerProfileId): void
    {
        abort_unless($user->isClient(), 403, 'Seçilmişlər yalnız müştəri üçündür');

        Favorite::query()
            ->where('user_id', $user->id)
            ->where('provider_profile_id', $providerProfileId)
            ->delete();

        unset($this->idsCache[$user->id]);
    }

    public function isFavorite(User $user, int $providerProfileId): bool
    {
        if (! $user->isClient()) {
            return false;
        }

        return in_array($providerProfileId, $this->idsFor($user), true);
    }

    /**
     * @return list<int>
     */
    public function idsFor(User $user): array
    {
        if (! $user->isClient()) {
            return [];
        }

        if (! array_key_exists($user->id, $this->idsCache)) {
            $this->idsCache[$user->id] = Favorite::query()
                ->where('user_id', $user->id)
                ->pluck('provider_profile_id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        return $this->idsCache[$user->id];
    }

    private function resolvableProfile(int $id): ProviderProfile
    {
        $profile = ProviderProfile::query()
            ->with(['user', 'category', 'categories'])
            ->find($id);

        abort_if(! $profile || ! $profile->is_active, 404, 'Provider not found');
        abort_if(! $profile->user || ! $profile->user->isProviderApproved(), 404, 'Provider not found');

        return $profile;
    }
}
