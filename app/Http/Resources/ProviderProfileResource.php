<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin \App\Models\ProviderProfile */
class ProviderProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'category_id' => $this->category_id,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'title' => $this->title,
            'bio' => $this->bio,
            'user_name' => $this->whenLoaded('user', fn () => $this->user?->name),
            'user_phone' => $this->whenLoaded('user', fn () => $this->user?->phone),
            'audio_intro_url' => $this->audio_intro_url
                ? Storage::disk('public')->url($this->audio_intro_url)
                : null,
            'is_verified' => $this->is_verified,
            'is_vip' => $this->is_vip,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'city' => $this->city,
            'district' => $this->district,
            'rating_avg' => $this->rating_avg,
            'rating_count' => $this->rating_count,
            'bumped_at' => $this->bumped_at?->toIso8601String(),
            'vip_expires_at' => $this->vip_expires_at?->toIso8601String(),
            'is_active' => $this->is_active,
            'schedules' => ScheduleResource::collection($this->whenLoaded('schedules')),
            'distance_km' => $this->when(isset($this->distance_km), $this->distance_km),
        ];
    }
}
