<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\ProviderProfile;
use App\Models\ServiceRequest;
use App\Repositories\ProviderProfileRepository;
use App\Repositories\RequestMatchRepository;
use App\Support\BumpQuota;
use Illuminate\Support\Collection;

class SearchService
{
    public function __construct(
        private readonly ProviderProfileRepository $profiles,
        private readonly RequestMatchRepository $matches,
    ) {}

    public function matchRequest(ServiceRequest $request): Collection
    {
        // Voice/text without a resolved category must not list every provider nearby.
        if (! filled($request->category_id)) {
            $criteria = $request->parsed_criteria ?? [];
            $criteria['search_meta'] = [
                'empty' => true,
                'missing_category' => true,
                'urgent' => (bool) $request->is_urgent,
                'radius_km' => (float) config('homeservice.search_radius_km', 50),
                'base_radius_km' => (float) config('homeservice.search_radius_km', 50),
                'expanded' => false,
                'dropped_category' => false,
                'dropped_area' => false,
                'dropped_schedule' => false,
            ];
            $request->forceFill(['parsed_criteria' => $criteria])->save();

            return collect();
        }

        $isUrgent = (bool) $request->is_urgent;
        $urgentRadius = (float) config('homeservice.urgent_radius_km', 5);
        $searchRadius = (float) config('homeservice.search_radius_km', 50);
        $baseRadius = $isUrgent ? $urgentRadius : $searchRadius;
        $minResults = max(1, (int) config('homeservice.search_min_results', 12));

        $criteria = $request->parsed_criteria ?? [];
        $desiredSlot = $criteria['time_slot'] ?? null;
        $districtId = ! empty($criteria['district_id']) ? (int) $criteria['district_id'] : null;
        $cityId = ! empty($criteria['city_id']) ? (int) $criteria['city_id'] : null;
        $repeatProviderIds = Booking::query()
            ->where('client_id', $request->user_id)
            ->whereIn('status', [Booking::SCHEDULED, Booking::COMPLETED])
            ->whereNotNull('provider_profile_id')
            ->pluck('provider_profile_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $merged = collect();
        $usedRadius = $baseRadius;
        $usedCategory = true;
        $usedArea = $districtId !== null || $cityId !== null;
        $usedSchedule = filled($desiredSlot);
        $found = false;
        $metaLocked = false;

        $attempts = $isUrgent
            ? [
                ['category' => true, 'district' => $districtId, 'city' => null, 'radius' => true, 'schedule' => true],
                ['category' => true, 'district' => null, 'city' => $cityId, 'radius' => true, 'schedule' => true],
                ['category' => true, 'district' => null, 'city' => null, 'radius' => true, 'schedule' => true],
                ['category' => true, 'district' => null, 'city' => null, 'radius' => true, 'schedule' => false],
            ]
            : [
                // Tight → wider. Merge hits so one district match does not hide peers.
                ['category' => true, 'district' => $districtId, 'city' => null, 'radius' => false, 'schedule' => true],
                ['category' => true, 'district' => null, 'city' => $cityId, 'radius' => false, 'schedule' => true],
                ['category' => true, 'district' => null, 'city' => null, 'radius' => true, 'schedule' => true],
                ['category' => true, 'district' => null, 'city' => null, 'radius' => true, 'schedule' => false],
            ];

        foreach ($attempts as $attempt) {
            // Explicit category must stay — never fall back to unrelated providers.
            if (filled($request->category_id) && empty($attempt['category'])) {
                continue;
            }
            $categoryId = $attempt['category'] ? $request->category_id : null;
            $slot = ($attempt['schedule'] ?? false) && filled($desiredSlot) ? $desiredSlot : null;
            $radii = $attempt['radius']
                ? ($isUrgent
                    ? [$baseRadius]
                    : array_values(array_unique([$baseRadius, 40.0, 80.0, 200.0])))
                : [9999.0];
            sort($radii);
            foreach ($radii as $radius) {
                $batch = $this->profiles->searchNearby(
                    $request->latitude,
                    $request->longitude,
                    $categoryId,
                    $radius,
                    50,
                    $attempt['city'],
                    $attempt['district'],
                    $attempt['radius'],
                    $slot,
                );
                if ($batch->isEmpty()) {
                    continue;
                }

                $merged = $merged
                    ->concat($batch)
                    ->unique(fn (ProviderProfile $p) => $p->id)
                    ->values();

                if (! $metaLocked) {
                    $found = true;
                    $metaLocked = true;
                    $usedRadius = $attempt['radius'] ? $radius : $baseRadius;
                    $usedCategory = (bool) $attempt['category'] && filled($request->category_id);
                    $usedArea = $attempt['district'] !== null || $attempt['city'] !== null;
                    $usedSchedule = filled($slot);
                }

                if ($merged->count() >= $minResults) {
                    break 2;
                }
            }
        }

        $providers = $merged;

        $criteria = $request->parsed_criteria ?? [];
        $criteria['search_meta'] = [
            'empty' => ! $found,
            'urgent' => $isUrgent,
            'radius_km' => $usedRadius,
            'base_radius_km' => $baseRadius,
            'expanded' => ! $isUrgent && $found && $usedRadius > $baseRadius + 0.01,
            'dropped_category' => $found && filled($request->category_id) && ! $usedCategory,
            'dropped_area' => $found && ($districtId || $cityId) && ! $usedArea,
            'dropped_schedule' => $found && filled($desiredSlot) && ! $usedSchedule,
            'result_count' => $providers->count(),
        ];
        $request->forceFill(['parsed_criteria' => $criteria])->save();

        $results = $providers->map(function (ProviderProfile $provider) use ($desiredSlot, $repeatProviderIds, $districtId) {
            $distance = (float) ($provider->distance_km ?? 0);
            $distanceScore = max(0, 100 - ($distance * 4));

            $scheduleScore = 50;
            if ($desiredSlot) {
                $hasSlot = $provider->schedules
                    ->where('is_available', true)
                    ->where('time_slot', $desiredSlot)
                    ->isNotEmpty();
                $scheduleScore = $hasSlot ? 100 : 20;
            }

            $verifiedBonus = $provider->is_verified ? 10 : 0;
            $vipBonus = $provider->is_vip ? 8 : 0;
            $ratingBonus = min(10, ((float) $provider->rating_avg) * 2);
            $repeatBonus = in_array((int) $provider->id, $repeatProviderIds, true) ? 12 : 0;
            $bumpActive = BumpQuota::isActive($provider->bumped_at);
            $bumpBonus = $bumpActive ? 6 : 0;
            $districtBonus = ($districtId && (int) $provider->district_id === $districtId) ? 10 : 0;

            $score = min(100, round(
                ($distanceScore * 0.45) +
                ($scheduleScore * 0.30) +
                $verifiedBonus +
                $vipBonus +
                $ratingBonus +
                $repeatBonus +
                $bumpBonus +
                $districtBonus,
                2
            ));

            return [
                'provider_profile_id' => $provider->id,
                'match_score' => $score,
                'distance_km' => round($distance, 2),
                'score_breakdown' => [
                    'distance' => round($distanceScore, 2),
                    'schedule' => $scheduleScore,
                    'verified' => $verifiedBonus,
                    'vip' => $vipBonus,
                    'rating' => $ratingBonus,
                    'repeat_client' => $repeatBonus > 0 ? 1 : 0,
                    'bump' => $bumpActive ? 1 : 0,
                    'district' => $districtBonus > 0 ? 1 : 0,
                ],
                'provider' => $provider,
            ];
        })->sortByDesc('match_score')->values();

        $this->matches->upsertMany(
            $request,
            $results->map(fn ($r) => [
                'provider_profile_id' => $r['provider_profile_id'],
                'match_score' => $r['match_score'],
                'distance_km' => $r['distance_km'],
                'score_breakdown' => $r['score_breakdown'],
            ])->all()
        );

        return $results;
    }
}
