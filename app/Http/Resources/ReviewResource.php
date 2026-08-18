<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Review */
class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'offer_id' => $this->offer_id,
            'reviewer_id' => $this->reviewer_id,
            'reviewee_id' => $this->reviewee_id,
            'provider_profile_id' => $this->provider_profile_id,
            'rating' => (int) $this->rating,
            'comment' => $this->comment,
            'reviewer_name' => $this->whenLoaded('reviewer', fn () => $this->reviewer?->name),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
