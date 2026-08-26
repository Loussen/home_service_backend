<?php

namespace App\Repositories;

use App\Models\AppString;

class AppStringRepository
{
    /** @var array<string, array<string, string>> */
    private array $dbCache = [];

    public function forLocale(string $locale): array
    {
        $default = (string) config('app_locales.default', 'az');
        $supported = config('app_locales.supported', ['az']);
        $locale = $this->normalize($locale);

        if (! in_array($locale, $supported, true)) {
            $locale = $default;
        }

        $files = config('app_strings', []);
        $fileDefault = $files[$default] ?? [];
        $fileLocale = $files[$locale] ?? [];
        $keys = array_keys($fileDefault);

        $dbLocale = $this->dbMap($locale);
        $dbDefault = $locale === $default ? [] : $this->dbMap($default);

        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $dbLocale[$key]
                ?? ($fileLocale[$key] ?? null)
                ?? ($dbDefault[$key] ?? null)
                ?? ($fileDefault[$key] ?? $key);
        }

        return $result;
    }

    public function supportedLocales(): array
    {
        return array_values(config('app_locales.supported', ['az']));
    }

    public function defaultLocale(): string
    {
        return (string) config('app_locales.default', 'az');
    }

    public function localeLabels(): array
    {
        $all = config('app_locales.labels', []);
        $supported = $this->supportedLocales();

        return array_intersect_key($all, array_flip($supported));
    }

    public function version(): int
    {
        return (int) config('app_locales.version', 1);
    }

    public function normalize(?string $locale): string
    {
        $locale = strtolower(trim((string) $locale));
        if ($locale === '') {
            return $this->defaultLocale();
        }

        // Accept-Language: en-US,en;q=0.9 → en
        if (str_contains($locale, ',')) {
            $locale = explode(',', $locale)[0];
        }
        if (str_contains($locale, ';')) {
            $locale = explode(';', $locale)[0];
        }
        if (str_contains($locale, '-')) {
            $locale = explode('-', $locale)[0];
        }

        return $locale;
    }

    /** @return array<string, string> */
    private function dbMap(string $locale): array
    {
        if (! isset($this->dbCache[$locale])) {
            $this->dbCache[$locale] = AppString::query()
                ->where('locale', $locale)
                ->pluck('value', 'key')
                ->all();
        }

        return $this->dbCache[$locale];
    }
}
