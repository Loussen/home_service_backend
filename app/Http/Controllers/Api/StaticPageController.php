<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StaticPage;
use App\Repositories\AppStringRepository;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StaticPageController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly AppStringRepository $strings) {}

    public function index(Request $request): JsonResponse
    {
        $locale = $this->localeFrom($request);

        return $this->success(StaticPage::menuItems($locale)->all());
    }

    public function show(Request $request, string $slug): JsonResponse
    {
        $locale = $this->localeFrom($request);
        $page = StaticPage::query()
            ->published()
            ->where('slug', $slug)
            ->first();

        if (! $page) {
            return $this->error('Səhifə tapılmadı', 404);
        }

        return $this->success([
            'slug' => $page->slug,
            'title' => $page->titleFor($locale),
            'body_html' => $page->bodyFor($locale),
        ]);
    }

    private function localeFrom(Request $request): string
    {
        return $this->strings->normalize(
            $request->query('locale') ?? $request->header('Accept-Language')
        );
    }
}
