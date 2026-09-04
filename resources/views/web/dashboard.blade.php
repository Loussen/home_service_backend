@extends('web.layout')

@section('title', 'My Sancho · Dashboard')
@section('page', 'dashboard')

@section('content')
    <section class="dash-hero">
        <div class="dash-hero-copy">
            <p class="eyebrow" data-i18n="web.dashboard.eyebrow">My Sancho marketplace</p>
            <h1 id="dash-title" data-i18n="web.dashboard.guest_title">{{ wt('web.dashboard.guest_title', 'Evdə lazım olanı tez tap') }}</h1>
            <p id="dash-subtitle" class="dash-lead" data-i18n="web.dashboard.guest_subtitle">
                {{ wt('web.dashboard.guest_subtitle', 'Səs və ya mətnlə sorğu göndər — uyğun xidmətçilər çıxır, CONNECT ilə yazış.') }}
            </p>
            <div class="dash-cta" id="dash-cta-guest">
                <a href="{{ route('web.login') }}" class="btn btn-primary btn-inline" data-i18n="web.dashboard.cta_start">Başla · Daxil ol</a>
            </div>
            <div class="dash-cta" id="dash-cta-client" hidden>
                <a href="{{ route('web.request') }}" class="btn btn-primary btn-inline" data-i18n="web.dashboard.cta_new_request">Yeni sorğu</a>
                <a href="{{ route('web.requests') }}" class="btn btn-outline btn-inline" data-i18n="web.nav.requests">Sorğularım</a>
                <a href="{{ route('web.chat') }}" class="btn btn-outline btn-inline" data-i18n="web.dashboard.cta_chats">Söhbətlər</a>
            </div>
            <div class="dash-cta" id="dash-cta-provider" hidden>
                <a href="{{ route('web.jobs') }}" class="btn btn-primary btn-inline" data-i18n="web.nav.jobs">Gələn işlər</a>
                <a href="{{ route('web.chat') }}" class="btn btn-outline btn-inline" data-i18n="web.dashboard.cta_chats">Söhbətlər</a>
            </div>
        </div>
        <div class="dash-hero-panel" aria-hidden="true">
            <div class="dash-orb dash-orb-a"></div>
            <div class="dash-orb dash-orb-b"></div>
            <div class="dash-flow">
                <span data-i18n="web.dashboard.flow_voice">Səs</span>
                <span class="dash-flow-arrow">→</span>
                <span>AI</span>
                <span class="dash-flow-arrow">→</span>
                <span>Match</span>
                <span class="dash-flow-arrow">→</span>
                <span>CONNECT</span>
            </div>
        </div>
    </section>

    <section class="dash-stats" id="dash-stats" hidden>
        <article class="dash-stat">
            <span class="dash-stat-label" data-i18n="web.dashboard.stat_balance">Balans</span>
            <strong id="dash-balance">—</strong>
        </article>
        <article class="dash-stat">
            <span class="dash-stat-label" id="dash-connect-label">CONNECT</span>
            <strong id="dash-connect">—</strong>
        </article>
        <article class="dash-stat">
            <span class="dash-stat-label" data-i18n="web.dashboard.stat_role">Rol</span>
            <strong id="dash-role">—</strong>
        </article>
    </section>

    <section class="dash-section">
        <div class="section-head">
            <h2 data-i18n="web.dashboard.quick_title">Tez keçidlər</h2>
        </div>
        <div class="dash-grid">
            <a href="{{ route('web.request') }}" class="dash-tile dash-tile-primary" data-role="client">
                <span class="dash-tile-kicker" data-i18n="web.role.client">Ailə</span>
                <strong data-i18n="web.nav.request">Sorğu yarat</strong>
                <p data-i18n="web.dashboard.tile_request_desc">Mətn və ünvan ilə xidmət axtar, match-ləri gör, CONNECT et.</p>
            </a>
            <a href="{{ route('web.jobs') }}" class="dash-tile dash-tile-primary" data-role="provider">
                <span class="dash-tile-kicker" data-i18n="web.role.provider">İcraçı</span>
                <strong data-i18n="web.nav.jobs">Gələn işlər</strong>
                <p data-i18n="web.dashboard.tile_jobs_desc">Uyğun sorğulara cavab ver və chat-də təklif göndər.</p>
            </a>
            <a href="{{ route('web.chat') }}" class="dash-tile" data-role="any">
                <span class="dash-tile-kicker" data-i18n="web.dashboard.tile_chat_kicker">Mesaj</span>
                <strong data-i18n="web.nav.chat">Chat</strong>
                <p data-i18n="web.dashboard.tile_chat_desc">Yazışma, təklif və razılaşma.</p>
            </a>
            <a href="{{ route('web.profile') }}" class="dash-tile" data-role="any">
                <span class="dash-tile-kicker" data-i18n="web.dashboard.tile_profile_kicker">Hesab</span>
                <strong data-i18n="web.nav.profile">Profil</strong>
                <p data-i18n="web.dashboard.tile_profile_desc">Ad, balans və hesab ayarları.</p>
            </a>
            <a href="{{ route('web.categories') }}" class="dash-tile" data-role="provider">
                <span class="dash-tile-kicker" data-i18n="web.dashboard.tile_categories_kicker">{{ wt('web.dashboard.tile_categories_kicker', 'Tag') }}</span>
                <strong data-i18n="web.nav.categories">Kateqoriyalar</strong>
                <p data-i18n="web.dashboard.tile_categories_desc">Maksimum 3 yarpaq kateqoriya seç.</p>
            </a>
        </div>
    </section>

    <section class="dash-section">
        <div class="section-head">
            <h2 data-i18n="web.dashboard.how_title">Necə işləyir</h2>
        </div>
        <div class="dash-steps">
            <article class="dash-step">
                <span class="dash-step-num">1</span>
                <strong data-i18n="web.dashboard.step1_title">Ailə sorğu göndərir</strong>
                <p data-i18n="web.dashboard.step1_desc">Nə lazımdır, harada və nə vaxt — qısa yazır.</p>
            </article>
            <article class="dash-step">
                <span class="dash-step-num">2</span>
                <strong data-i18n="web.dashboard.step2_title">Match + CONNECT</strong>
                <p data-i18n="web.dashboard.step2_desc">Uyğun icraçılar çıxır; ailə chat açır.</p>
            </article>
            <article class="dash-step">
                <span class="dash-step-num">3</span>
                <strong data-i18n="web.dashboard.step3_title">İcraçı təklif göndərir</strong>
                <p data-i18n="web.dashboard.step3_desc">Vaxt və qiymət — ailə qəbul və ya imtina edir.</p>
            </article>
        </div>
    </section>

    <section class="dash-section">
        @include('web.partials.app-download', ['compact' => false])
    </section>
@endsection
