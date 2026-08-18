<?php

namespace App\Http\Resources;

use App\Http\Resources\ProviderProfileResource;
use App\Support\MatchReasons;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\ServiceRequest */
class ServiceRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'raw_audio_url' => $this->raw_audio_url,
            'transcribed_text' => $this->transcribed_text,
            'parsed_criteria' => $this->parsed_criteria,
            'is_urgent' => $this->is_urgent,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'address' => $this->address,
            'status' => $this->status,
            'bumped_at' => $this->bumped_at?->toIso8601String(),
            'urgent_until' => $this->urgent_until?->toIso8601String(),
            'matches' => $this->whenLoaded('matches', function () {
                return $this->matches->map(fn ($m) => [
                    'id' => $m->id,
                    'match_score' => $m->match_score,
                    'distance_km' => $m->distance_km,
                    'score_breakdown' => $m->score_breakdown,
                    'reasons' => MatchReasons::for($m, $this->resource),
                    'provider' => $m->providerProfile
                        ? new ProviderProfileResource($m->providerProfile)
                        : null,
                ]);
            }),
            'matches_count' => $this->whenLoaded('matches', fn () => $this->matches->count()),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
