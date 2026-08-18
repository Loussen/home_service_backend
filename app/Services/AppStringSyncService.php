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
     * @return array{created: int, updated: int, total: int}
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

        return [
            'created' => $created,
            'updated' => $updated,
            'total' => AppString::query()->count(),
        ];
    }
}
