<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = [
        'parent_id',
        'slug',
        'name_az',
        'name_en',
        'name_ru',
        'icon',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function nameFor(?string $locale = null): string
    {
        $default = (string) config('app_locales.default', 'az');
        $locale = strtolower(trim((string) $locale));
        if ($locale === '') {
            $locale = $default;
        }
        if (str_contains($locale, ',')) {
            $locale = explode(',', $locale)[0];
        }
        if (str_contains($locale, '-')) {
            $locale = explode('-', $locale)[0];
        }

        foreach (array_values(array_unique(array_filter([$locale, $default, 'az']))) as $code) {
            $value = $this->getAttribute("name_{$code}");
            if (is_string($value) && trim($value) !== '') {
                return $value;
            }
        }

        return (string) $this->name_az;
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function providerProfiles(): HasMany
    {
        return $this->hasMany(ProviderProfile::class);
    }

    public function assignedProfiles(): BelongsToMany
    {
        return $this->belongsToMany(ProviderProfile::class, 'category_provider_profile')
            ->withTimestamps();
    }

    public function serviceRequests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class);
    }

    /**
     * @return list<int>
     */
    public static function idsWithDescendants(int $categoryId): array
    {
        $all = static::query()->get(['id', 'parent_id']);
        $ids = [$categoryId];
        $changed = true;
        while ($changed) {
            $changed = false;
            foreach ($all as $row) {
                if ($row->parent_id && in_array((int) $row->parent_id, $ids, true)
                    && ! in_array((int) $row->id, $ids, true)) {
                    $ids[] = (int) $row->id;
                    $changed = true;
                }
            }
        }

        return $ids;
    }

    /**
     * @param  list<int>  $ids
     */
    public static function assertAllLeaves(array $ids): void
    {
        if ($ids === []) {
            return;
        }
        $nonLeaves = static::query()
            ->whereIn('parent_id', $ids)
            ->pluck('parent_id')
            ->unique()
            ->all();
        abort_if($nonLeaves !== [], 422, 'Yalnız alt kateqoriya seçmək olar');
    }

    /**
     * @return array<int, string>
     */
    public static function treeLabelMap(?int $excludeId = null, bool $leavesOnly = false): array
    {
        $all = static::query()->orderBy('sort_order')->orderBy('id')->get();
        $byId = $all->keyBy('id');
        $exclude = $excludeId ? self::idsWithDescendants($excludeId) : [];
        $labels = [];

        foreach ($all as $category) {
            if (in_array((int) $category->id, $exclude, true)) {
                continue;
            }
            if ($leavesOnly && $all->contains(fn ($row) => (int) $row->parent_id === (int) $category->id)) {
                continue;
            }
            $parts = [];
            $current = $category;
            $guard = 0;
            while ($current && $guard++ < 12) {
                array_unshift($parts, $current->name_az);
                $current = $current->parent_id ? $byId->get($current->parent_id) : null;
            }
            $labels[$category->id] = implode(' → ', $parts);
        }

        return $labels;
    }

    public static function rootsWithChildren(): EloquentCollection
    {
        return static::query()
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->with(['children' => function ($q) {
                $q->where('is_active', true)->with(['children' => function ($q2) {
                    $q2->where('is_active', true)->with(['children' => function ($q3) {
                        $q3->where('is_active', true)->orderBy('sort_order');
                    }])->orderBy('sort_order');
                }])->orderBy('sort_order');
            }])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }
}
