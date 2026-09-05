<?php

namespace App\Repositories;

use App\Models\Category;
use App\Models\District;
use App\Models\ProviderProfile;
use App\Support\BumpQuota;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ProviderProfileRepository
{
    public function create(array $data): ProviderProfile
    {
        return ProviderProfile::create($data);
    }

    public function update(ProviderProfile $profile, array $data): ProviderProfile
    {
        $profile->update($data);

        return $profile->fresh(['category', 'categories', 'schedules']);
    }

    public function findForUser(int $userId, int $profileId): ?ProviderProfile
    {
        return ProviderProfile::with(['category', 'categories', 'schedules'])
            ->where('user_id', $userId)
            ->where('id', $profileId)
            ->first();
    }

    /** Active profile for any authenticated viewer (client match results, etc.). */
    public function findPublic(int $profileId): ?ProviderProfile
    {
        return ProviderProfile::with(['user', 'category', 'categories', 'schedules'])
            ->where('id', $profileId)
            ->where('is_active', true)
            ->whereHas('user', function ($q) {
                $q->where('provider_approval_status', 'approved')
                    ->where('status', 'active');
            })
            ->first();
    }

    public function listForUser(int $userId): Collection
    {
        return ProviderProfile::with(['category', 'categories', 'schedules'])
            ->where('user_id', $userId)
            ->latest()
            ->get();
    }

    /**
     * Nearby active providers. Distance via Haversine in PHP (SQLite + MySQL safe).
     *
     * @return Collection<int, ProviderProfile>
     */
    public function searchNearby(
        float $lat,
        float $lng,
        ?int $categoryId = null,
        float $radiusKm = 15,
        int $limit = 50,
        ?int $cityId = null,
        ?int $districtId = null,
        bool $applyRadius = true,
        ?string $timeSlot = null,
    ): Collection {
        $query = ProviderProfile::query()
            ->with(['user', 'category', 'categories', 'schedules'])
            ->where('is_active', true)
            ->whereHas('user', function ($q) {
                $q->where('provider_approval_status', 'approved')
                    ->where('status', 'active');
            })
            ->where(function ($q) {
                $q->whereNull('full_until')
                    ->orWhere('full_until', '<=', now());
            });

        if ($categoryId) {
            $ids = Category::idsWithDescendants($categoryId);
            $query->where(function ($q) use ($ids) {
                $q->whereIn('category_id', $ids)
                    ->orWhereHas('categories', fn ($c) => $c->whereIn('categories.id', $ids));
            });
        }

        if ($districtId) {
            // Soft district: exact district OR same city with no district set.
            // Hard district-only hid peers who only pinned a city / map point.
            $districtCityId = $cityId
                ?? District::query()->whereKey($districtId)->value('city_id');
            $query->where(function ($q) use ($districtId, $districtCityId) {
                $q->where('district_id', $districtId);
                if ($districtCityId) {
                    $q->orWhere(function ($q2) use ($districtCityId) {
                        $q2->where('city_id', $districtCityId)
                            ->whereNull('district_id');
                    });
                }
            });
        } elseif ($cityId) {
            $query->where('city_id', $cityId);
        }

        if ($timeSlot) {
            $query->where(function ($q) use ($timeSlot) {
                $q->whereDoesntHave('schedules')
                    ->orWhereHas(
                        'schedules',
                        fn ($s) => $s->where('time_slot', $timeSlot)->where('is_available', true)
                    );
            });
        }

        $bumpHours = BumpQuota::hours();
        $bumpCutoff = now()->subHours($bumpHours);
        $bumpBoostKm = BumpQuota::boostKm();

        return $query->get()
            ->map(function (ProviderProfile $profile) use ($lat, $lng, $bumpCutoff, $bumpBoostKm) {
                $profile->distance_km = $this->haversineKm(
                    $lat,
                    $lng,
                    (float) $profile->latitude,
                    (float) $profile->longitude
                );
                $bumped = $profile->bumped_at && $profile->bumped_at->gt($bumpCutoff);
                $profile->sort_km = (float) $profile->distance_km - ($bumped ? $bumpBoostKm : 0);

                return $profile;
            })
            ->filter(fn (ProviderProfile $p) => ! $applyRadius || $p->distance_km <= $radiusKm)
            ->sortBy([
                ['is_vip', 'desc'],
                ['sort_km', 'asc'],
            ])
            ->take($limit)
            ->values();
    }

    public function syncSchedules(ProviderProfile $profile, array $slots): void
    {
        $profile->schedules()->delete();

        $rows = collect($slots)->map(fn (array $slot) => [
            'provider_profile_id' => $profile->id,
            'day_of_week' => $slot['day_of_week'],
            'time_slot' => $slot['time_slot'],
            'is_available' => $slot['is_available'] ?? true,
            'created_at' => now(),
            'updated_at' => now(),
        ])->all();

        if ($rows !== []) {
            DB::table('schedules')->insert($rows);
        }
    }

    private function haversineKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earth = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return $earth * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
