<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class StaticPage extends Model
{
    protected $fillable = [
        'slug',
        'sort_order',
        'is_published',
        'show_in_menu',
        'title_az',
        'title_en',
        'title_ru',
        'body_az',
        'body_en',
        'body_ru',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'show_in_menu' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scopeInMenu(Builder $query): Builder
    {
        return $query->where('show_in_menu', true);
    }

    public function titleFor(?string $locale = null): string
    {
        return $this->localized('title', $locale) ?: $this->title_az;
    }

    public function bodyFor(?string $locale = null): string
    {
        return $this->localized('body', $locale) ?: (string) $this->body_az;
    }

    /**
     * @return Collection<int, array{slug: string, title: string, sort_order: int}>
     */
    public static function menuItems(?string $locale = null): Collection
    {
        return static::query()
            ->published()
            ->inMenu()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (self $page) => [
                'slug' => $page->slug,
                'title' => $page->titleFor($locale),
                'sort_order' => (int) $page->sort_order,
            ])
            ->values();
    }

    private function localized(string $field, ?string $locale): string
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

        $candidates = array_values(array_unique(array_filter([
            $locale,
            $default,
            'az',
        ])));

        foreach ($candidates as $code) {
            $col = "{$field}_{$code}";
            $value = $this->getAttribute($col);
            if (is_string($value) && trim($value) !== '') {
                return $value;
            }
        }

        return '';
    }
};
