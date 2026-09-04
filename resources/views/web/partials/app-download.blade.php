@php
    $compact = $compact ?? false;
    $iosUrl = trim((string) config('homeservice.app_store_url', ''));
    $androidUrl = trim((string) config('homeservice.play_store_url', ''));
    $soon = wt('web.app_download.soon', 'Tezliklə');
    $iosLabel = wt('web.app_download.ios', 'App Store');
    $androidLabel = wt('web.app_download.android', 'Google Play');
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
                class="app-store-badge"
                target="_blank"
                rel="noopener noreferrer"
                aria-label="{{ $iosLabel }}"
            >
                <img
                    src="{{ asset('images/brand/appstore.png') }}"
                    alt="{{ $iosLabel }}"
                    width="147"
                    height="50"
                    loading="lazy"
                    decoding="async"
                >
            </a>
        @else
            <span class="app-store-badge is-soon" aria-disabled="true">
                <img
                    src="{{ asset('images/brand/appstore.png') }}"
                    alt="{{ $iosLabel }}"
                    width="147"
                    height="50"
                    loading="lazy"
                    decoding="async"
                >
                <span class="app-store-soon" data-i18n="web.app_download.soon">{{ $soon }}</span>
            </span>
        @endif

        @if ($androidUrl !== '')
            <a
                href="{{ $androidUrl }}"
                class="app-store-badge"
                target="_blank"
                rel="noopener noreferrer"
                aria-label="{{ $androidLabel }}"
            >
                <img
                    src="{{ asset('images/brand/playstore.png') }}"
                    alt="{{ $androidLabel }}"
                    width="170"
                    height="50"
                    loading="lazy"
                    decoding="async"
                >
            </a>
        @else
            <span class="app-store-badge is-soon" aria-disabled="true">
                <img
                    src="{{ asset('images/brand/playstore.png') }}"
                    alt="{{ $androidLabel }}"
                    width="170"
                    height="50"
                    loading="lazy"
                    decoding="async"
                >
                <span class="app-store-soon" data-i18n="web.app_download.soon">{{ $soon }}</span>
            </span>
        @endif
    </div>
</div>
