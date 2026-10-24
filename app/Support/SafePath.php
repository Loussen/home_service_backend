<?php

namespace App\Support;

/**
 * Path checks that must not explode under open_basedir (hosting panels).
 */
final class SafePath
{
    public static function isReadableFile(string $path): bool
    {
        if ($path === '') {
            return false;
        }

        set_error_handler(static fn () => true);
        try {
            return is_file($path) && is_readable($path);
        } finally {
            restore_error_handler();
        }
    }
}
