<?php

namespace App\Http\Resources;

use App\Support\RequestLocale;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Category */
class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $locale = RequestLocale::from($request);

        return [
            'id' => $this->id,
            'parent_id' => $this->parent_id,
            'slug' => $this->slug,
            'name' => $this->nameFor($locale),
            'name_az' => $this->name_az,
            'name_en' => $this->name_en,
            'name_ru' => $this->name_ru,
            'icon' => $this->icon,
            'sort_order' => $this->sort_order,
            'children' => $this->whenLoaded(
                'children',
                fn () => CategoryResource::collection($this->children)
            ),
        ];
    }
}
