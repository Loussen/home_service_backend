<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Offer */
class OfferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'conversation_id' => $this->conversation_id,
            'proposed_by' => $this->proposed_by,
            'scheduled_at' => $this->scheduled_at?->toIso8601String(),
            'duration_hours' => $this->duration_hours,
            'price_azn' => $this->price_azn,
            'note' => $this->note,
            'status' => $this->status,
            'accepted_at' => $this->accepted_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'reviews' => ReviewResource::collection($this->whenLoaded('reviews')),
        ];
    }
}
