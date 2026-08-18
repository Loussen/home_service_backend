<?php

namespace App\Support;

use App\Http\Resources\ProviderProfileResource;
use App\Models\RequestMatch;
use App\Models\ServiceRequest;
use Illuminate\Support\Collection;

final class GroupedMatches
{
    /**
     * One card per person: merge multiple profiles of the same user.
     *
     * @param  Collection<int, RequestMatch>  $matches
     * @return list<array<string, mixed>>
     */
    public static function forClient(Collection $matches, ServiceRequest $request): array
    {
        return $matches
            ->filter(fn (RequestMatch $m) => $m->providerProfile !== null)
            ->groupBy(fn (RequestMatch $m) => (int) $m->providerProfile->user_id)
            ->map(function (Collection $group) use ($request) {
                /** @var RequestMatch $best */
                $best = $group->sortByDesc('match_score')->first();
                $profile = $best->providerProfile;

                $categories = $group
                    ->flatMap(function (RequestMatch $m) {
                        $p = $m->providerProfile;
                        $cats = $p->relationLoaded('categories')
                            ? $p->categories
                            : collect();
                        if ($p->category && $cats->every(fn ($c) => (int) $c->id !== (int) $p->category->id)) {
                            $cats = $cats->push($p->category);
                        }

                        return $cats;
                    })
                    ->unique('id')
                    ->values();

                $profile->setRelation('categories', $categories);
                $profile->is_verified = $group->contains(
                    fn (RequestMatch $m) => (bool) $m->providerProfile?->is_verified
                );
                $profile->is_vip = $group->contains(
                    fn (RequestMatch $m) => (bool) $m->providerProfile?->is_vip
                );

                $breakdown = $best->score_breakdown ?? [];
                if ($group->contains(fn (RequestMatch $m) => (int) (($m->score_breakdown ?? [])['repeat_client'] ?? 0) === 1)) {
                    $breakdown['repeat_client'] = 1;
                    $best->score_breakdown = $breakdown;
                }

                return [
                    'id' => $best->id,
                    'match_score' => $best->match_score,
                    'distance_km' => $group->min('distance_km'),
                    'score_breakdown' => $breakdown,
                    'reasons' => MatchReasons::for($best, $request),
                    'merged_profile_count' => $group->count(),
                    'provider' => new ProviderProfileResource($profile),
                ];
            })
            ->sortByDesc('match_score')
            ->values()
            ->all();
    }
}
