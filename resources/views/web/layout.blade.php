<!doctype html>
<html lang="{{ $webLocale ?? 'az' }}" class="i18n-boot">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="referrer" content="origin">
    <link rel="icon" type="image/jpeg" href="{{ asset('images/brand/logo-light.jpg') }}">
    <title>@yield('title', 'My Sancho')</title>
    <style>html.i18n-boot body{visibility:hidden}</style>
    <noscript><style>html.i18n-boot body{visibility:visible!important}</style></noscript>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @if (file_exists(public_path('hot')))
        @vite(['resources/css/web.css'])
    @else
        <link rel="stylesheet" href="{{ asset('css/web.css') }}?v={{ @filemtime(public_path('css/web.css')) }}">
    @endif
    <script>
        window.__MS_I18N__ = {
            locale: @json($webLocale ?? 'az'),
            strings: @json($webStrings ?? []),
            static_pages: @json(($staticMenuPages ?? collect())->values())
        };
        (function () {
            try {
                var KEY = 'mysancho_locale';
                var fromLs = localStorage.getItem(KEY);
                var server = (document.documentElement.lang || 'az').toLowerCase();
                if (fromLs) {
                    fromLs = String(fromLs).toLowerCase();
                    document.cookie = KEY + '=' + encodeURIComponent(fromLs) + ';path=/;max-age=31536000;SameSite=Lax';
                    if (fromLs !== server && !sessionStorage.getItem('ms_locale_fix')) {
                        sessionStorage.setItem('ms_locale_fix', '1');
                        location.replace(location.href);
                        return;
                    }
                }
                sessionStorage.removeItem('ms_locale_fix');
                if (localStorage.getItem('mysancho_web_token')) {
                    document.documentElement.classList.add('has-token');
                }
                setTimeout(function () {
                    document.documentElement.classList.remove('i18n-boot');
                }, 2000);
            } catch (e) {}
        })();
    </script>
</head>
@php
    $path = trim(request()->path(), '/');
    $webLocale = $webLocale ?? 'az';
    $webSupportedLocales = $webSupportedLocales ?? ['az', 'en', 'ru'];
    $staticMenuPages = $staticMenuPages ?? collect();
@endphp
<body
    data-page="@yield('page', 'generic')"
    data-locale="{{ $webLocale }}"
    data-maps-key="{{ config('homeservice.google_maps_browser_key') }}"
    data-maps-js="{{ filled(config('homeservice.google_maps_browser_key')) ? '1' : '0' }}"
    data-conversation-id="@yield('conversation_id', '')"
    data-provider-id="{{ $providerId ?? '' }}"
    class="font-sans"
>
<a class="skip-link" href="#main" data-i18n="web.skip">{{ wt('web.skip', 'Məzmuna keç') }}</a>

<header class="site-header">
    <div class="site-header-inner">
        <div class="header-brand-row">
            <a href="{{ route('web.app') }}" class="logo" aria-label="{{ wt('web.logo_aria', 'My Sancho ana səhifə') }}" data-i18n-aria="web.logo_aria">
                <img
                    src="{{ asset('images/brand/logo-color.jpg') }}"
                    alt=""
                    class="logo-img"
                    width="40"
                    height="40"
                >
                <span class="logo-text">My Sancho</span>
            </a>

            @if ($staticMenuPages->isNotEmpty())
                <button
                    type="button"
                    class="header-info-toggle"
                    id="header-info-toggle"
                    aria-expanded="false"
                    aria-controls="header-info-drawer"
                    data-i18n-aria="web.nav.info_menu"
                    aria-label="{{ wt('web.nav.info_menu', 'Məlumat') }}"
                >
                    <span class="header-info-toggle-bars" aria-hidden="true"></span>
                </button>

                <nav class="header-info-nav" id="header-info-nav" aria-label="Info">
                    @foreach ($staticMenuPages as $item)
                        <a
                            href="{{ route('web.static', ['slug' => $item['slug']]) }}"
                            class="{{ $path === 'p/'.$item['slug'] ? 'active' : '' }}"
                        >{{ $item['title'] }}</a>
                    @endforeach
                </nav>
            @endif
        </div>

        <div class="header-tools">
            <div class="lang-switcher" id="lang-switcher" role="group" aria-label="Language">
                @foreach ($webSupportedLocales as $code)
                    <button
                        type="button"
                        class="lang-btn {{ $webLocale === $code ? 'is-active' : '' }}"
                        data-locale="{{ $code }}"
                        aria-pressed="{{ $webLocale === $code ? 'true' : 'false' }}"
                    >{{ strtoupper($code) }}</button>
                @endforeach
            </div>

            <div class="header-auth" id="header-auth">
                <div id="auth-guest" class="auth-guest">
                    <a href="{{ route('web.login') }}" class="btn btn-primary btn-auth" aria-label="{{ wt('web.auth.cta_aria', 'Daxil ol və ya qeydiyyat') }}" data-i18n-aria="web.auth.cta_aria">
                        <span class="label-full" data-i18n="web.auth.login">{{ wt('web.auth.login', 'Daxil ol') }}</span>
                        <span class="label-short" data-i18n="web.auth.login_short">{{ wt('web.auth.login_short', 'Giriş') }}</span>
                    </a>
                </div>
                <div id="auth-user" class="auth-user" hidden>
                    <div class="user-menu" id="user-menu">
                        <div class="auth-user-cluster">
                            <button type="button" class="auth-user-card" id="user-menu-toggle" aria-expanded="false" aria-haspopup="true" aria-controls="user-menu-panel">
                                <span class="auth-avatar" id="auth-avatar" aria-hidden="true">?</span>
                                <span class="auth-user-meta">
                                    <strong id="auth-name">{{ wt('web.auth.user', 'İstifadəçi') }}</strong>
                                    <span class="auth-user-subline">
                                        <span id="auth-role">—</span>
                                    </span>
                                </span>
                                <span class="user-menu-caret" aria-hidden="true"></span>
                            </button>
                            <button type="button" class="auth-status-pill" id="auth-profile-status" hidden aria-label="{{ wt('web.status.aria', 'Profil statusu') }}" data-i18n-aria="web.status.aria"></button>
                        </div>
                        <div class="user-menu-panel" id="user-menu-panel" hidden role="menu">
                            <a href="{{ route('web.request') }}" class="user-menu-item {{ $path === 'request' ? 'active' : '' }}" data-role="client" role="menuitem" data-i18n="web.nav.request">{{ wt('web.nav.request', 'Sorğu yarat') }}</a>
                            <a href="{{ route('web.requests') }}" class="user-menu-item {{ $path === 'requests' ? 'active' : '' }}" data-role="client" role="menuitem" data-i18n="web.nav.requests">{{ wt('web.nav.requests', 'Sorğularım') }}</a>
                            <a href="{{ route('web.jobs') }}" class="user-menu-item {{ $path === 'jobs' ? 'active' : '' }}" data-role="provider" role="menuitem" data-i18n="web.nav.jobs">{{ wt('web.nav.jobs', 'Gələn işlər') }}</a>
                            <a href="{{ route('web.chat') }}" class="user-menu-item {{ str_starts_with($path, 'chat') ? 'active' : '' }}" data-role="any" role="menuitem" data-i18n="web.nav.chat">{{ wt('web.nav.chat', 'Chat') }}</a>
                            <a href="{{ route('web.profile') }}" class="user-menu-item {{ $path === 'profile' ? 'active' : '' }}" data-role="any" role="menuitem" data-i18n="web.nav.profile">{{ wt('web.nav.profile', 'Profil') }}</a>
                            <a href="{{ route('web.categories') }}" class="user-menu-item {{ $path === 'categories' ? 'active' : '' }}" data-role="provider" role="menuitem" data-i18n="web.nav.categories">{{ wt('web.nav.categories', 'Kateqoriyalar') }}</a>
                            <button type="button" id="header-logout" class="user-menu-item user-menu-danger" data-role="any" role="menuitem" data-i18n="web.nav.logout">{{ wt('web.nav.logout', 'Çıxış') }}</button>
                        </div>
                    </div>
                </div>
                <strong id="auth-status" class="sr-only" aria-live="polite">{{ wt('web.auth.guest', 'Qonaq') }}</strong>
            </div>
        </div>
    </div>

    @if ($staticMenuPages->isNotEmpty())
        <div class="header-info-drawer" id="header-info-drawer" hidden>
            <nav class="header-info-drawer-nav" id="header-info-drawer-nav" aria-label="Info">
                @foreach ($staticMenuPages as $item)
                    <a
                        href="{{ route('web.static', ['slug' => $item['slug']]) }}"
                        class="{{ $path === 'p/'.$item['slug'] ? 'active' : '' }}"
                    >{{ $item['title'] }}</a>
                @endforeach
            </nav>
        </div>
    @endif
</header>
<script>
    (function () {
        try {
            if (!localStorage.getItem('mysancho_web_token')) return;
            var guest = document.getElementById('auth-guest');
            var user = document.getElementById('auth-user');
            if (guest) guest.hidden = true;
            if (user) user.hidden = false;
            var snap = JSON.parse(localStorage.getItem('mysancho_web_auth_snap') || 'null');
            if (!snap) return;
            var nameEl = document.getElementById('auth-name');
            var roleEl = document.getElementById('auth-role');
            var avatar = document.getElementById('auth-avatar');
            if (nameEl && snap.name) nameEl.textContent = snap.name;
            if (roleEl && snap.active_role) {
                roleEl.setAttribute('data-role-code', snap.active_role);
                if (snap.role) roleEl.textContent = snap.role;
            }
            if (avatar) {
                if (snap.avatar_url) {
                    avatar.classList.add('has-photo');
                    avatar.innerHTML = '<img src="' + String(snap.avatar_url).replace(/"/g, '&quot;') + '" alt="" width="36" height="36">';
                } else if (snap.initial) {
                    avatar.classList.remove('has-photo');
                    avatar.textContent = snap.initial;
                }
            }
            var role = snap.active_role;
            if (role === 'client' || role === 'provider') {
                document.documentElement.setAttribute('data-auth-role', role);
            }

            // Dashboard hero: paint logged-in state before web-app.js / applyI18n.
            if (document.body && document.body.getAttribute('data-page') === 'dashboard') {
                var guestCta = document.getElementById('dash-cta-guest');
                var clientCta = document.getElementById('dash-cta-client');
                var providerCta = document.getElementById('dash-cta-provider');
                var stats = document.getElementById('dash-stats');
                var title = document.getElementById('dash-title');
                var subtitle = document.getElementById('dash-subtitle');
                var isProvider = role === 'provider';
                if (guestCta) guestCta.hidden = true;
                if (clientCta) clientCta.hidden = isProvider;
                if (providerCta) providerCta.hidden = !isProvider;
                if (stats) stats.hidden = false;
                var locale = (document.documentElement.lang || 'az').toLowerCase();
                var name = snap.name || '';
                if (title && name) {
                    title.removeAttribute('data-i18n');
                    if (locale === 'en') title.textContent = 'Hello, ' + name;
                    else if (locale === 'ru') title.textContent = 'Здравствуйте, ' + name;
                    else title.textContent = 'Salam, ' + name;
                }
                if (subtitle) {
                    subtitle.removeAttribute('data-i18n');
                    if (isProvider) {
                        if (locale === 'en') {
                            subtitle.textContent = 'Review incoming jobs and send offers in chat.';
                        } else if (locale === 'ru') {
                            subtitle.textContent = 'Смотрите входящие заказы и отправляйте предложения в чате.';
                        } else {
                            subtitle.textContent = 'Gələn işlərə bax, chat-də təklif göndər.';
                        }
                    } else if (locale === 'en') {
                        subtitle.textContent = 'Create a new request and CONNECT from matches.';
                    } else if (locale === 'ru') {
                        subtitle.textContent = 'Создайте новый запрос и CONNECT из совпадений.';
                    } else {
                        subtitle.textContent = 'Yeni sorğu yarat, match-lərdən CONNECT et.';
                    }
                }
                var roleDash = document.getElementById('dash-role');
                if (roleDash && snap.role) roleDash.textContent = snap.role;
            }
        } catch (e) {}
    })();
</script>

<div class="wrap">
    <main id="main" class="page-enter">
        @yield('content')
    </main>
</div>

<script>
    (function () {
        try {
            var role = document.documentElement.getAttribute('data-auth-role');
            if (role !== 'client' && role !== 'provider') return;
            document.querySelectorAll('[data-role]').forEach(function (node) {
                var need = node.getAttribute('data-role');
                if (!need || need === 'any') {
                    node.hidden = false;
                    return;
                }
                node.hidden = need !== role;
            });
        } catch (e) {}
    })();
</script>

<footer class="site-footer">
    <div class="site-footer-inner">
        <p class="footer-brand">My Sancho</p>
        <p class="footer-copy" data-i18n="web.footer.tagline">{{ wt('web.footer.tagline', 'Ailə ilə xidmətçini birləşdirən marketplace') }}</p>
        @if ($staticMenuPages->isNotEmpty())
            <nav class="footer-links" id="footer-links" aria-label="Info">
                @foreach ($staticMenuPages as $item)
                    <a href="{{ route('web.static', ['slug' => $item['slug']]) }}">{{ $item['title'] }}</a>
                @endforeach
            </nav>
        @endif
    </div>
</footer>

<div id="toast-stack" class="toast-stack" aria-live="polite" aria-atomic="true"></div>

<div id="page-loader" class="page-loader" hidden aria-live="assertive" aria-busy="false">
    <div class="page-loader-card">
        <div class="page-loader-spinner" aria-hidden="true"></div>
        <p data-i18n="web.loading">{{ wt('web.loading', 'Yüklənir…') }}</p>
    </div>
</div>

<script src="{{ asset('js/web-app.js') }}?v={{ @filemtime(public_path('js/web-app.js')) }}"></script>
</body>
</html>
