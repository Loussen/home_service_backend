<?php

namespace App\Repositories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

class CategoryRepository
{
    public function active(): Collection
    {
        return Category::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    public function tree(): Collection
    {
        return Category::rootsWithChildren();
    }

    public function findBySlug(string $slug): ?Category
    {
        return Category::where('slug', $slug)->first();
    }

    public function findById(int $id): ?Category
    {
        return Category::find($id);
    }

    /**
     * @return list<array{slug: string, name_az: string, name_en: ?string}>
     */
    public function leafCatalog(): array
    {
        return Category::query()
            ->where('is_active', true)
            ->whereDoesntHave('children')
            ->orderBy('name_az')
            ->get(['slug', 'name_az', 'name_en'])
            ->map(fn (Category $c) => [
                'slug' => $c->slug,
                'name_az' => $c->name_az,
                'name_en' => $c->name_en,
            ])
            ->all();
    }
}
