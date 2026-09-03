<?php

namespace App\Providers;

use App\Support\RuntimeSettings;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Always load helpers (does not rely on `composer dump-autoload` on deploy).
        require_once app_path('Support/helpers.php');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RuntimeSettings::applyToConfig();
    }
}
