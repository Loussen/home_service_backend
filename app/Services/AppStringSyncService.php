<?php

namespace App\Services;

use App\Models\AppString;

class AppStringSyncService
{
    /**
     * config/app_strings/{locale}.php → app_strings cədvəli.
     * Yeni açarları əlavə edir. Mövcud DB dəyərləri (admin redaktəsi) saxlanılır
     * əgər $overwriteExisting = false.
     *
     * @return array{created: int, updated: int, fixed_copies: int, total: int}
     */
    public function syncFromFiles(bool $overwriteExisting = false): array
    {
        $all = config('app_strings', []);
        $created = 0;
        $updated = 0;

        foreach ($all as $locale => $pairs) {
            if (! is_array($pairs)) {
                continue;
            }

            foreach ($pairs as $key => $value) {
                $existing = AppString::query()
                    ->where('key', (string) $key)
                    ->where('locale', (string) $locale)
                    ->first();

                if ($existing === null) {
                    AppString::query()->create([
                        'key' => (string) $key,
                        'locale' => (string) $locale,
                        'value' => (string) $value,
                    ]);
                    $created++;

                    continue;
                }

                if ($overwriteExisting && $existing->value !== (string) $value) {
                    $existing->update(['value' => (string) $value]);
                    $updated++;
                }
            }
        }

        $fixed = $overwriteExisting ? 0 : $this->fixCopiedLocaleRows();

        return [
            'created' => $created,
            'updated' => $updated,
            'fixed_copies' => $fixed,
            'total' => AppString::query()->count(),
        ];
    }

    /**
     * EN/RU DB sətirləri hələ AZ mətni saxlayırsa (köhnə sync/copy), fayldakı tərcümə ilə əvəz et.
     * Brand/eyni qalan açarlar (Chat, CONNECT) faylda da eyni olduğu üçün toxunulmur.
     */
    public function fixCopiedLocaleRows(): int
    {
        $all = config('app_strings', []);
        $az = is_array($all['az'] ?? null) ? $all['az'] : [];
        $fixed = 0;

        foreach (['en', 'ru'] as $locale) {
            $fileLocale = is_array($all[$locale] ?? null) ? $all[$locale] : [];
            if ($fileLocale === []) {
                continue;
            }

            $rows = AppString::query()->where('locale', $locale)->get();
            foreach ($rows as $row) {
                $key = (string) $row->key;
                $fileValue = $fileLocale[$key] ?? null;
                if ($fileValue === null) {
                    continue;
                }

                $azValue = (string) ($az[$key] ?? '');
                $dbValue = (string) $row->value;

                // Copied AZ into EN/RU: DB matches AZ file, but locale file differs.
                if ($azValue !== '' && $dbValue === $azValue && $fileValue !== $azValue) {
                    $row->update(['value' => (string) $fileValue]);
                    $fixed++;
                }
            }
        }

        return $fixed;
    }
}
