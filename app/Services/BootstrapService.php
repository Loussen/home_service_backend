<?php

namespace App\Services;

use App\Repositories\AppStringRepository;

class BootstrapService
{
    public function __construct(
        private readonly AppStringRepository $strings,
        private readonly FcmClient $fcm,
    ) {}

    public function payload(?string $locale = null): array
    {
        $hs = config('homeservice');
        $locale = $this->strings->normalize($locale);
        $stringMap = $this->strings->forLocale($locale);

        return [
            'version' => $this->strings->version(),
            'locale' => $locale,
            'default_locale' => $this->strings->defaultLocale(),
            'supported_locales' => $this->strings->supportedLocales(),
            'locale_labels' => $this->strings->localeLabels(),
            'strings' => $stringMap,
            'config' => [
                'max_category_tags' => $hs['max_category_tags'],
                'fees' => [
                    'welcome_bonus' => $hs['welcome_bonus'],
                    'bump' => $hs['bump_up_fee'],
                    'urgent' => $hs['urgent_fee'],
                    'vip' => $hs['vip_fee'],
                    'verified' => $hs['verified_fee'],
                ],
                'search_radius_km' => $hs['search_radius_km'],
                'places_configured' => filled($hs['google_maps_api_key']),
                'onboarding_steps' => [
                    ['id' => 'name', 'title' => $stringMap['onboarding.step.name'] ?? ''],
                    ['id' => 'categories', 'title' => $stringMap['onboarding.step.categories'] ?? ''],
                    ['id' => 'location', 'title' => $stringMap['onboarding.step.location'] ?? ''],
                    ['id' => 'schedule', 'title' => $stringMap['onboarding.step.schedule'] ?? ''],
                    ['id' => 'about', 'title' => $stringMap['onboarding.step.about'] ?? ''],
                ],
            ],
            'flags' => [
                'voice_search' => filter_var(env('FEATURE_VOICE_SEARCH', true), FILTER_VALIDATE_BOOL),
                'maps_enabled' => filter_var(env('FEATURE_MAPS', true), FILTER_VALIDATE_BOOL),
                'push_configured' => $this->fcm->isConfigured(),
            ],
        ];
    }
}
