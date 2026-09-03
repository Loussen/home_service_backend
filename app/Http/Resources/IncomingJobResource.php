<?php

namespace App\Http\Resources;

use App\Support\MatchReasons;
use App\Support\RequestLocale;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\RequestMatch */
class IncomingJobResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $sr = $this->serviceRequest;
        $locale = RequestLocale::from($request);

        return [
            'match_id' => $this->id,
            'match_score' => $this->match_score,
            'distance_km' => $this->distance_km,
            'reasons' => MatchReasons::for($this->resource, $sr),
            'provider_profile_id' => $this->provider_profile_id,
            'profile_title' => $this->providerProfile?->title,
            'is_urgent' => (bool) $sr?->is_urgent,
            'request' => $sr ? [
                'id' => $sr->id,
                'status' => $sr->status,
                'transcribed_text' => $sr->transcribed_text,
                'address' => $sr->address,
                'latitude' => $sr->latitude,
                'longitude' => $sr->longitude,
                'category' => $sr->category ? [
                    'id' => $sr->category->id,
                    'name' => $sr->category->nameFor($locale),
                    'name_az' => $sr->category->name_az,
                ] : null,
                'created_at' => $sr->created_at?->toIso8601String(),
            ] : null,
            'client' => $sr?->user ? [
                'id' => $sr->user->id,
                'name' => $sr->user->name,
            ] : null,
        ];
    }
}
