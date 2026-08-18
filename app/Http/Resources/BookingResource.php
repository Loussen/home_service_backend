<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Booking */
class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $me = $request->user();
        $other = $me && $this->client_id === $me->id ? $this->provider : $this->client;
        $profile = $this->whenLoaded('providerProfile');

        return [
            'id' => $this->id,
            'offer_id' => $this->offer_id,
            'conversation_id' => $this->conversation_id,
            'scheduled_at' => $this->scheduled_at?->toIso8601String(),
            'duration_hours' => $this->duration_hours,
            'price_azn' => $this->price_azn,
            'note' => $this->note,
            'status' => $this->status,
            'completed_at' => $this->completed_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'other_user' => $other ? [
                'id' => $other->id,
                'name' => $other->name,
            ] : null,
            'profile_title' => $this->relationLoaded('providerProfile')
                ? $this->providerProfile?->title
                : null,
            'category_name' => $this->relationLoaded('providerProfile')
                ? ($this->providerProfile?->category?->name_az ?? null)
                : null,
        ];
    }
}
