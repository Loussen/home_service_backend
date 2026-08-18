<?php

namespace App\Http\Resources;

use App\Support\GroupedMatches;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\ServiceRequest */
class ServiceRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $grouped = $this->relationLoaded('matches')
            ? GroupedMatches::forClient($this->matches, $this->resource)
            : null;

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
            'matches' => $this->when($grouped !== null, $grouped),
            'matches_count' => $this->when($grouped !== null, fn () => count($grouped ?? [])),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
