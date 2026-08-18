<?php

namespace App\Repositories;

use App\Models\RequestMatch;
use App\Models\ServiceRequest;

class RequestMatchRepository
{
    public function upsertMany(ServiceRequest $request, array $matches): void
    {
        foreach ($matches as $match) {
            RequestMatch::updateOrCreate(
                [
                    'service_request_id' => $request->id,
                    'provider_profile_id' => $match['provider_profile_id'],
                ],
                [
                    'match_score' => $match['match_score'],
                    'distance_km' => $match['distance_km'] ?? null,
                    'score_breakdown' => $match['score_breakdown'] ?? null,
                ]
            );
        }
    }
}
