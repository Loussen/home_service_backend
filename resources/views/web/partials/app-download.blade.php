@php
    $compact = $compact ?? false;
    $iosUrl = trim((string) config('homeservice.app_store_url', ''));
    $androidUrl = trim((string) config('homeservice.play_store_url', ''));
    $soon = wt('web.app_download.soon', 'Tezliklə');
@endphp
<div class="app-download {{ $compact ? 'app-download--compact' : 'app-download--panel' }}">
    @unless ($compact)
        <div class="app-download-copy">
            <h2 class="app-download-title" data-i18n="web.app_download.title">{{ wt('web.app_download.title', 'Mobil tətbiq') }}</h2>
            <p class="app-download-body" data-i18n="web.app_download.body">{{ wt('web.app_download.body', 'Səsli sorğu və match üçün My Sancho-nu iOS və Android-də yükləyin.') }}</p>
        </div>
    @else
        <p class="app-download-kicker" data-i18n="web.app_download.title">{{ wt('web.app_download.title', 'Mobil tətbiq') }}</p>
    @endunless

    <div class="app-download-actions">
        @if ($iosUrl !== '')
            <a
                href="{{ $iosUrl }}"
                class="app-store-btn"
                target="_blank"
                rel="noopener noreferrer"
                data-i18n="web.app_download.ios"
            >{{ wt('web.app_download.ios', 'App Store') }}</a>
        @else
            <span class="app-store-btn is-soon" aria-disabled="true">
                <span data-i18n="web.app_download.ios">{{ wt('web.app_download.ios', 'App Store') }}</span>
                <span class="app-store-soon" data-i18n="web.app_download.soon">{{ $soon }}</span>
            </span>
        @endif

        @if ($androidUrl !== '')
            <a
                href="{{ $androidUrl }}"
                class="app-store-btn"
                target="_blank"
                rel="noopener noreferrer"
                data-i18n="web.app_download.android"
            >{{ wt('web.app_download.android', 'Google Play') }}</a>
        @else
            <span class="app-store-btn is-soon" aria-disabled="true">
                <span data-i18n="web.app_download.android">{{ wt('web.app_download.android', 'Google Play') }}</span>
                <span class="app-store-soon" data-i18n="web.app_download.soon">{{ $soon }}</span>
            </span>
        @endif
    </div>
</div>
