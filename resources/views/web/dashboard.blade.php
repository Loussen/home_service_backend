@extends('web.layout')

@section('title', 'MySancho · Dashboard')
@section('page', 'dashboard')

@section('content')
    <section class="dash-hero">
        <div class="dash-hero-copy">
            <p class="eyebrow">MySancho marketplace</p>
            <h1 id="dash-title">Evdə lazım olanı&nbsp;tez tap</h1>
            <p id="dash-subtitle" class="dash-lead">
                Səs və ya mətnlə sorğu göndər — uyğun xidmətçilər çıxır, CONNECT ilə yazış.
            </p>
            <div class="dash-cta" id="dash-cta-guest">
                <a href="{{ route('web.login') }}" class="btn btn-primary btn-inline">Başla · Giriş</a>
            </div>
            <div class="dash-cta" id="dash-cta-client" hidden>
                <a href="{{ route('web.request') }}" class="btn btn-primary btn-inline">Yeni sorğu</a>
                <a href="{{ route('web.chat') }}" class="btn btn-outline btn-inline">Söhbətlər</a>
            </div>
            <div class="dash-cta" id="dash-cta-provider" hidden>
                <a href="{{ route('web.jobs') }}" class="btn btn-primary btn-inline">Gələn işlər</a>
                <a href="{{ route('web.chat') }}" class="btn btn-outline btn-inline">Söhbətlər</a>
            </div>
        </div>
        <div class="dash-hero-panel" aria-hidden="true">
            <div class="dash-orb dash-orb-a"></div>
            <div class="dash-orb dash-orb-b"></div>
            <div class="dash-flow">
                <span>Səs</span>
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
            <span class="dash-stat-label">Balans</span>
            <strong id="dash-balance">—</strong>
        </article>
        <article class="dash-stat">
            <span class="dash-stat-label">CONNECT qalıq</span>
            <strong id="dash-connect">—</strong>
        </article>
        <article class="dash-stat">
            <span class="dash-stat-label">Rol</span>
            <strong id="dash-role">—</strong>
        </article>
    </section>

    <section class="dash-section">
        <div class="section-head">
            <h2>Tez keçidlər</h2>
        </div>
        <div class="dash-grid">
            <a href="{{ route('web.request') }}" class="dash-tile dash-tile-primary" data-role="client">
                <span class="dash-tile-kicker">Ailə</span>
                <strong>Sorğu yarat</strong>
                <p>Mətn və ünvan ilə xidmət axtar, match-ləri gör, CONNECT et.</p>
            </a>
            <a href="{{ route('web.jobs') }}" class="dash-tile dash-tile-primary" data-role="provider">
                <span class="dash-tile-kicker">İcraçı</span>
                <strong>Gələn işlər</strong>
                <p>Uyğun sorğulara cavab ver və chat-də təklif göndər.</p>
            </a>
            <a href="{{ route('web.chat') }}" class="dash-tile" data-role="any">
                <span class="dash-tile-kicker">Mesaj</span>
                <strong>Chat</strong>
                <p>Yazışma, təklif və razılaşma.</p>
            </a>
            <a href="{{ route('web.profile') }}" class="dash-tile" data-role="any">
                <span class="dash-tile-kicker">Hesab</span>
                <strong>Profil</strong>
                <p>Ad, balans və hesab ayarları.</p>
            </a>
            <a href="{{ route('web.categories') }}" class="dash-tile" data-role="provider">
                <span class="dash-tile-kicker">Tag</span>
                <strong>Kateqoriyalar</strong>
                <p>Maksimum 3 yarpaq kateqoriya seç.</p>
            </a>
            <a href="{{ route('web.onboarding') }}" class="dash-tile" data-role="any">
                <span class="dash-tile-kicker">Start</span>
                <strong>Onboarding</strong>
                <p>Hesab → kateqoriya → məkan addımları.</p>
            </a>
        </div>
    </section>

    <section class="dash-section">
        <div class="section-head">
            <h2>Necə işləyir</h2>
        </div>
        <div class="dash-steps">
            <article class="dash-step">
                <span class="dash-step-num">1</span>
                <strong>Ailə sorğu göndərir</strong>
                <p>Nə lazımdır, harada və nə vaxt — qısa yazır.</p>
            </article>
            <article class="dash-step">
                <span class="dash-step-num">2</span>
                <strong>Match + CONNECT</strong>
                <p>Uyğun icraçılar çıxır; ailə chat açır.</p>
            </article>
            <article class="dash-step">
                <span class="dash-step-num">3</span>
                <strong>İcraçı təklif göndərir</strong>
                <p>Vaxt və qiymət — ailə qəbul və ya imtina edir.</p>
            </article>
        </div>
    </section>
@endsection
