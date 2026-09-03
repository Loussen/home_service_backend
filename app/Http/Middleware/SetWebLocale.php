<?php

namespace App\Http\Middleware;

use App\Models\StaticPage;
use App\Repositories\AppStringRepository;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetWebLocale
{
    public function __construct(private readonly AppStringRepository $strings) {}

    public function handle(Request $request, Closure $next): Response
    {
        $raw = $request->cookie('mysancho_locale')
            ?? $request->query('locale')
            ?? $request->cookie('mysancho_web_locale');
        $locale = $this->strings->normalize(is_string($raw) ? $raw : null);

        app()->setLocale($locale);
        view()->share('webLocale', $locale);
        view()->share('webLocaleLabels', $this->strings->localeLabels());
        view()->share('webSupportedLocales', $this->strings->supportedLocales());
        try {
            view()->share('staticMenuPages', StaticPage::menuItems($locale));
        } catch (\Throwable) {
            view()->share('staticMenuPages', collect());
        }

        return $next($request);
    }
}
