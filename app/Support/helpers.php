<?php

if (! function_exists('wt')) {
    /**
     * Web UI string for the current request locale (shared by SetWebLocale).
     */
    function wt(string $key, ?string $fallback = null): string
    {
        $strings = app('view')->shared('webStrings');
        if (is_array($strings) && isset($strings[$key]) && $strings[$key] !== '') {
            return (string) $strings[$key];
        }

        return $fallback ?? $key;
    }
}
