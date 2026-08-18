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
            'category_ids' => $this->whenLoaded(
                'categories',
                fn () => $this->categories->pluck('id')->values(),
                default: array_values(array_filter([$this->category_id]))
            ),
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
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
            'city_id' => $this->city_id,
            'district_id' => $this->district_id,
            'city' => $this->city,
            'district' => $this->district,
            'rating_avg' => $this->rating_avg,
            'rating_count' => $this->rating_count,
            'bumped_at' => $this->bumped_at?->toIso8601String(),
            'vip_expires_at' => $this->vip_expires_at?->toIso8601String(),
            'is_active' => $this->is_active,
            'is_full' => $this->isFull(),
            'full_until' => $this->full_until?->toIso8601String(),
            'quiet_hours_start' => $this->quietHoursStartHm(),
            'quiet_hours_end' => $this->quietHoursEndHm(),
            'schedules' => ScheduleResource::collection($this->whenLoaded('schedules')),
            'distance_km' => $this->when(isset($this->distance_km), $this->distance_km),
        ];
    }
}
