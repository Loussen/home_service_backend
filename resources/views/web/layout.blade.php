<!doctype html>
<html lang="az">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="referrer" content="origin">
    <title>@yield('title', 'MySancho')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    {{-- Production: static CSS (serverdə Node/Vite lazım deyil). Local `npm run dev` → public/hot. --}}
    @if (file_exists(public_path('hot')))
        @vite(['resources/css/web.css'])
    @else
        <link rel="stylesheet" href="{{ asset('css/web.css') }}?v={{ @filemtime(public_path('css/web.css')) }}">
    @endif
    <script>
        (function () {
            try {
                if (localStorage.getItem('mysancho_web_token')) {
                    document.documentElement.classList.add('has-token');
                }
            } catch (e) {}
        })();
    </script>
</head>
@php
    $path = trim(request()->path(), '/');
@endphp
<body
    data-page="@yield('page', 'generic')"
    data-maps-key="{{ config('homeservice.google_maps_browser_key') }}"
    data-maps-js="{{ filled(config('homeservice.google_maps_browser_key')) ? '1' : '0' }}"
    data-conversation-id="@yield('conversation_id', '')"
    data-provider-id="{{ $providerId ?? '' }}"
    class="font-sans"
>
<a class="skip-link" href="#main">Məzmuna keç</a>

<header class="site-header">
    <div class="site-header-inner">
        <a href="{{ route('web.app') }}" class="logo" aria-label="MySancho ana səhifə">
            <span class="logo-mark" aria-hidden="true"></span>
            <span class="logo-text">MySancho</span>
        </a>

        <div class="header-auth" id="header-auth">
            <div id="auth-guest" class="auth-guest">
                <a href="{{ route('web.login') }}" class="btn btn-outline btn-auth">Giriş</a>
                <a href="{{ route('web.login') }}" class="btn btn-primary btn-auth" title="Qeydiyyat">
                    <span class="label-full">Qeydiyyat</span>
                    <span class="label-short">Qeyd.</span>
                </a>
            </div>
            <div id="auth-user" class="auth-user" hidden>
                <div class="user-menu" id="user-menu">
                    <button type="button" class="auth-user-card" id="user-menu-toggle" aria-expanded="false" aria-haspopup="true" aria-controls="user-menu-panel">
                        <span class="auth-avatar" id="auth-avatar" aria-hidden="true">?</span>
                        <span class="auth-user-meta">
                            <strong id="auth-name">İstifadəçi</strong>
                            <span id="auth-role">—</span>
                        </span>
                        <span class="user-menu-caret" aria-hidden="true"></span>
                    </button>
                    <div class="user-menu-panel" id="user-menu-panel" hidden role="menu">
                        <a href="{{ route('web.request') }}" class="user-menu-item {{ $path === 'request' ? 'active' : '' }}" data-role="client" role="menuitem">Sorğu yarat</a>
                        <a href="{{ route('web.requests') }}" class="user-menu-item {{ $path === 'requests' ? 'active' : '' }}" data-role="client" role="menuitem">Sorğularım</a>
                        <a href="{{ route('web.jobs') }}" class="user-menu-item {{ $path === 'jobs' ? 'active' : '' }}" data-role="provider" role="menuitem">Gələn işlər</a>
                        <div class="user-menu-status" data-role="provider" id="menu-profile-status" hidden>
                            <span class="user-menu-status-label">Profil statusu</span>
                            <span class="user-menu-status-value" id="menu-profile-status-value">—</span>
                        </div>
                        <a href="{{ route('web.chat') }}" class="user-menu-item {{ str_starts_with($path, 'chat') ? 'active' : '' }}" data-role="any" role="menuitem">Chat</a>
                        <a href="{{ route('web.profile') }}" class="user-menu-item {{ $path === 'profile' ? 'active' : '' }}" data-role="any" role="menuitem">Profil</a>
                        <a href="{{ route('web.categories') }}" class="user-menu-item {{ $path === 'categories' ? 'active' : '' }}" data-role="provider" role="menuitem">Kateqoriyalar</a>
                        <a href="{{ route('web.onboarding') }}" class="user-menu-item {{ $path === 'onboarding' ? 'active' : '' }}" data-role="any" role="menuitem">Onboarding</a>
                        <button type="button" id="header-logout" class="user-menu-item user-menu-danger" data-role="any" role="menuitem">Çıxış</button>
                    </div>
                </div>
            </div>
            <strong id="auth-status" class="sr-only" aria-live="polite">Qonaq</strong>
        </div>
    </div>
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
            if (roleEl && snap.role) roleEl.textContent = snap.role;
            if (avatar && snap.initial) avatar.textContent = snap.initial;
            var role = snap.active_role;
            if (role === 'client' || role === 'provider') {
                document.documentElement.setAttribute('data-auth-role', role);
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
    // Rol UI: API-dən əvvəl snap ilə — ailə/xidmətçi flash olmasın
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
        <p class="footer-brand">MySancho</p>
        <p class="footer-copy">Ailə ilə xidmətçini birləşdirən marketplace</p>
    </div>
</footer>

<div id="toast-stack" class="toast-stack" aria-live="polite" aria-atomic="true"></div>

<div id="page-loader" class="page-loader" hidden aria-live="assertive" aria-busy="false">
    <div class="page-loader-card">
        <div class="page-loader-spinner" aria-hidden="true"></div>
        <p>Yüklənir…</p>
    </div>
</div>

{{-- Logic qalır static (Vite yalnız CSS). Eyni /api/v1. --}}
<script src="{{ asset('js/web-app.js') }}?v={{ @filemtime(public_path('js/web-app.js')) }}"></script>
</body>
</html>
