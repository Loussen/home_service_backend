<?php

namespace App\Support;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class RuntimeSettings
{
    public const CACHE_KEY = 'app_runtime_settings';

    /**
     * Form field => config path.
     *
     * @return array<string, string>
     */
    public static function map(): array
    {
        return [
            'locales_supported' => 'app_locales.supported',
            'locales_default' => 'app_locales.default',
            'welcome_bonus' => 'homeservice.welcome_bonus',
            'wallet_packages' => 'homeservice.wallet_packages',
            'connect_free_quota' => 'homeservice.connect_free_quota',
            'connect_free_days' => 'homeservice.connect_free_days',
            'connect_daily_limit' => 'homeservice.connect_daily_limit',
            'connect_fee' => 'homeservice.connect_fee',
            'urgent_fee' => 'homeservice.urgent_fee',
            'urgent_hours' => 'homeservice.urgent_hours',
            'urgent_daily_limit' => 'homeservice.urgent_daily_limit',
            'urgent_radius_km' => 'homeservice.urgent_radius_km',
            'bump_up_fee' => 'homeservice.bump_up_fee',
            'bump_hours' => 'homeservice.bump_hours',
            'bump_daily_limit' => 'homeservice.bump_daily_limit',
            'bump_boost_km' => 'homeservice.bump_boost_km',
            'vip_fee' => 'homeservice.vip_fee',
            'vip_days' => 'homeservice.vip_days',
            'verified_fee' => 'homeservice.verified_fee',
            'search_radius_km' => 'homeservice.search_radius_km',
            'max_category_tags' => 'homeservice.max_category_tags',
            'feature_voice_search' => 'homeservice.feature_voice_search',
            'feature_maps' => 'homeservice.feature_maps',
            'feature_push' => 'homeservice.feature_push',
            'otp_ttl_minutes' => 'homeservice.otp_ttl_minutes',
            'otp_max_attempts' => 'homeservice.otp_max_attempts',
            'otp_send_max' => 'homeservice.otp_send_max',
            'otp_send_window_minutes' => 'homeservice.otp_send_window_minutes',
            'otp_resend_seconds' => 'homeservice.otp_resend_seconds',
            'otp_allow_debug_code' => 'homeservice.otp_allow_debug_code',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function stored(): array
    {
        if (! self::tableReady()) {
            return [];
        }

        return Cache::remember(self::CACHE_KEY, 3600, function () {
            return AppSetting::query()->pluck('value', 'key')->all();
        });
    }

    /**
     * @return array<string, mixed>
     */
    public static function formState(): array
    {
        $state = [];
        foreach (self::map() as $field => $path) {
            $state[$field] = self::stored()[$field] ?? config($path);
        }

        $packages = $state['wallet_packages'] ?? [10, 30, 50];
        $state['wallet_packages'] = array_values(array_map(
            fn ($v) => is_array($v) ? ($v['amount'] ?? $v) : $v,
            is_array($packages) ? $packages : [10, 30, 50],
        ));

        $supported = $state['locales_supported'] ?? ['az'];
        if (! is_array($supported)) {
            $supported = array_filter(explode(',', (string) $supported));
        }
        $state['locales_supported'] = array_values($supported);

        return $state;
    }

    /**
     * @param  array<string, mixed>  $state
     */
    public static function save(array $state): void
    {
        $supported = array_values(array_filter(
            is_array($state['locales_supported'] ?? []) ? $state['locales_supported'] : [],
            fn ($code) => is_string($code) && $code !== '',
        ));
        if ($supported === []) {
            $supported = ['az'];
        }
        $known = array_keys(config('app_locales.labels', ['az' => 'Azərbaycan']));
        $supported = array_values(array_intersect($supported, $known));
        if ($supported === []) {
            $supported = ['az'];
        }
        $state['locales_supported'] = $supported;

        $default = (string) ($state['locales_default'] ?? 'az');
        if (! in_array($default, $supported, true)) {
            $default = $supported[0];
        }
        $state['locales_default'] = $default;

        $packages = $state['wallet_packages'] ?? [10, 30, 50];
        if (! is_array($packages)) {
            $packages = [10, 30, 50];
        }
        $state['wallet_packages'] = array_values(array_filter(array_map(
            function ($row) {
                $n = is_array($row) ? ($row['amount'] ?? reset($row)) : $row;

                return is_numeric($n) ? (float) $n : null;
            },
            $packages,
        )));
        if ($state['wallet_packages'] === []) {
            $state['wallet_packages'] = [10, 30, 50];
        }

        $previous = self::stored();
        $version = (int) ($previous['locales_version'] ?? config('app_locales.version', 1));

        foreach (array_keys(self::map()) as $field) {
            if (! array_key_exists($field, $state)) {
                continue;
            }
            AppSetting::query()->updateOrCreate(
                ['key' => $field],
                ['value' => $state[$field]],
            );
        }

        AppSetting::query()->updateOrCreate(
            ['key' => 'locales_version'],
            ['value' => $version + 1],
        );

        Cache::forget(self::CACHE_KEY);
        self::applyToConfig();
    }

    public static function resetToDefaults(): void
    {
        if (! self::tableReady()) {
            return;
        }
        AppSetting::query()->delete();
        Cache::forget(self::CACHE_KEY);
        self::applyToConfig();
    }

    public static function applyToConfig(): void
    {
        if (! self::tableReady()) {
            return;
        }

        $stored = self::stored();
        foreach (self::map() as $field => $path) {
            if (! array_key_exists($field, $stored)) {
                continue;
            }
            config([$path => $stored[$field]]);
        }

        if (isset($stored['locales_version'])) {
            config(['app_locales.version' => (int) $stored['locales_version']]);
        }

        $supported = config('app_locales.supported', ['az']);
        if (is_string($supported)) {
            $supported = array_values(array_filter(explode(',', $supported)));
            config(['app_locales.supported' => $supported]);
        }
    }

    private static function tableReady(): bool
    {
        try {
            return Schema::hasTable('app_settings');
        } catch (\Throwable) {
            return false;
        }
    }
}
