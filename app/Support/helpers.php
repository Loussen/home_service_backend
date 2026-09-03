<?php

if (! function_exists('wt')) {
    /**
     * Web UI string for the current request locale (shared by SetWebLocale).
     * Never throws — returns fallback / key if strings are missing.
     */
    function wt(string $key, ?string $fallback = null): string
    {
        try {
            $strings = app('view')->shared('webStrings');
            if (is_array($strings) && isset($strings[$key]) && is_string($strings[$key]) && $strings[$key] !== '') {
                return $strings[$key];
            }
        } catch (\Throwable) {
            // View / container not ready — use fallback.
        }

        return $fallback ?? $key;
    }
}
