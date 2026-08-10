<?php

namespace App\Services;

use App\Models\ProviderProfile;
use App\Models\ServiceRequest;
use App\Repositories\CategoryRepository;
use App\Repositories\ProviderProfileRepository;
use App\Repositories\RequestMatchRepository;
use Illuminate\Support\Collection;

class SearchService
{
    public function __construct(
        private readonly ProviderProfileRepository $profiles,
        private readonly CategoryRepository $categories,
        private readonly RequestMatchRepository $matches,
    ) {}

    public function matchRequest(ServiceRequest $request): Collection
    {
        $radius = (float) config('homeservice.search_radius_km', 15);
        $providers = $this->profiles->searchNearby(
            $request->latitude,
            $request->longitude,
            $request->category_id,
            $radius
        );

        $criteria = $request->parsed_criteria ?? [];
        $desiredSlot = $criteria['time_slot'] ?? null;

        $results = $providers->map(function (ProviderProfile $provider) use ($desiredSlot) {
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

            $score = min(100, round(
                ($distanceScore * 0.45) +
                ($scheduleScore * 0.30) +
                $verifiedBonus +
                $vipBonus +
                $ratingBonus,
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
