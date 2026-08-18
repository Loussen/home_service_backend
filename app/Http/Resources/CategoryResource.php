<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Category */
class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'parent_id' => $this->parent_id,
            'slug' => $this->slug,
            'name_az' => $this->name_az,
            'name_en' => $this->name_en,
            'icon' => $this->icon,
            'sort_order' => $this->sort_order,
            'children' => $this->whenLoaded(
                'children',
                fn () => CategoryResource::collection($this->children)
            ),
        ];
    }
}
