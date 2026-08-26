<!doctype html>
<html lang="az">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="referrer" content="origin">
    <title>@yield('title', 'MySancho Web')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;0,9..40,800;1,9..40,400&family=Fraunces:opsz,wght@9..144,600;9..144,700&display=swap" rel="stylesheet">
    @vite(['resources/css/web.css'])
</head>
@php
    $path = request()->path();
@endphp
<body
    data-page="@yield('page', 'generic')"
    data-maps-key="{{ config('homeservice.google_maps_browser_key') }}"
    data-maps-js="{{ filled(config('homeservice.google_maps_browser_key')) ? '1' : '0' }}"
    data-conversation-id="@yield('conversation_id', '')"
    class="font-sans"
>
<div class="wrap">
    <header class="header">
        <div>
            <h1 class="brand-mark">MySancho</h1>
            <p class="mt-2 max-w-sm text-[0.95rem] font-medium text-ink">
                Eyni API, eyni qayda — müasir marketplace, etibarlı yol yoldaşı.
            </p>
        </div>
        <div class="badge-status">
            <span>Status</span>
            <strong id="auth-status">Qonaq</strong>
        </div>
    </header>

    <nav class="top-nav" aria-label="Əsas naviqasiya">
        <a href="{{ route('web.login') }}" class="{{ $path === 'web/login' ? 'active' : '' }}">Login</a>
        <a href="{{ route('web.onboarding') }}" class="{{ $path === 'web/onboarding' ? 'active' : '' }}">Onboarding</a>
        <a href="{{ route('web.categories') }}" class="{{ $path === 'web/categories' ? 'active' : '' }}">Kateqoriyalar</a>
        <a href="{{ route('web.profile') }}" class="{{ $path === 'web/profile' ? 'active' : '' }}">Profil</a>
        <a href="{{ route('web.request') }}" class="{{ $path === 'web/request' ? 'active' : '' }}">Sorğu</a>
        <a href="{{ route('web.chat') }}" class="{{ str_starts_with($path, 'web/chat') ? 'active' : '' }}">Chat</a>
        <a href="{{ route('web.jobs') }}" class="{{ $path === 'web/jobs' ? 'active' : '' }}">İşlər</a>
    </nav>

    <main>
        @yield('content')
    </main>
</div>
<div id="toast-stack" class="toast-stack" aria-live="polite" aria-atomic="true"></div>
{{-- Logic qalır static (Vite yalnız CSS). Eyni /api/v1. --}}
<script src="{{ asset('js/web-app.js') }}?v={{ @filemtime(public_path('js/web-app.js')) }}"></script>
</body>
</html>
