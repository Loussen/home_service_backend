@extends('web.layout')

@section('title', 'MySancho · Profil')
@section('page', 'profile')

@section('content')
    <header class="page-hero">
        <p class="eyebrow">Hesab</p>
        <h1>Profil</h1>
        <p id="profile-hero-sub" data-role="client" hidden>Ad, şəkil, balans — ailə hesabı.</p>
        <p id="profile-hero-sub-provider" data-role="provider" hidden>Şəxsi məlumat, şəkil, audio və xidmətçi profili.</p>
    </header>

    <section class="card card-hero approval-banner" id="provider-pending-banner" hidden>
        <h2 id="provider-pending-title">Təsdiq gözlənilir</h2>
        <p id="provider-pending-text" class="muted">
            Sorğunuz 1 saat ərzində baxılacaq. Təsdiqləndikdən sonra iş sorğuları gələcək.
        </p>
    </section>

    <div class="grid-main">
        <section class="card">
            <h2>İstifadəçi</h2>
            <div class="stack">
                <div class="avatar-upload">
                    <div class="avatar-shell">
                        <button type="button" id="avatar-pick" class="avatar-frame" aria-label="Profil şəkli seç">
                            <img id="profile-avatar-img" class="avatar-preview" alt="" hidden>
                            <span id="profile-avatar-fallback" class="avatar-fallback" aria-hidden="true">?</span>
                        </button>
                        <span class="avatar-camera" aria-hidden="true">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
                                <circle cx="12" cy="13" r="4"/>
                            </svg>
                        </span>
                    </div>
                    <div class="avatar-upload-meta">
                        <p class="avatar-upload-title">Profil şəkli</p>
                        <p class="avatar-upload-hint muted">JPG, PNG və ya WebP · maks. 5 MB</p>
                        <button type="button" id="upload-avatar" class="btn-link-brand">Şəkil seç / dəyiş</button>
                    </div>
                    <input id="profile-avatar-file" type="file" accept="image/jpeg,image/png,image/webp" hidden>
                </div>
                <input id="profile-name" type="text" placeholder="Ad Soyad">
                <p class="muted">Rol: <b id="profile-role-label">—</b> · Balans: <b id="profile-balance">—</b></p>
                <p class="muted" id="profile-approval-label" hidden></p>
                <button type="button" id="save-profile" class="btn btn-primary">Profili yenilə</button>
            </div>
        </section>

        <section class="card" data-role="client" id="client-profile-panel" hidden>
            <h2>Ailə hesabı</h2>
            <p class="muted">Xidmət axtarır, CONNECT edir və chat-də razılaşırsınız.</p>
            <div class="stack mt">
                <a href="{{ route('web.request') }}" class="btn btn-primary">Yeni sorğu yarat</a>
                <a href="{{ route('web.requests') }}" class="btn btn-outline">Sorğularım</a>
                <a href="{{ route('web.chat') }}" class="btn btn-outline">Söhbətlər</a>
                <a href="{{ route('web.app') }}" class="btn btn-outline">Ana səhifə</a>
            </div>
        </section>

        <section class="card" data-role="provider" id="provider-profile-panel" hidden>
            <h2>Xidmətçi profili</h2>
            <p class="muted">Kateqoriyalar səhifəsində seçdiyin tag-lər burada istifadə olunur. Audio intro admin təsdiqinə kömək edir (maks. 20 san).</p>
            <div class="stack">
                <input id="provider-title" type="text" placeholder="Başlıq (məs: Uşaq baxıcısı)">
                <textarea id="provider-bio" rows="4" placeholder="Qısa bio"></textarea>
                <div class="form-row form-row-2">
                    <input id="provider-city" type="text" placeholder="Şəhər" readonly class="input-readonly" tabindex="-1">
                    <input id="provider-district" type="text" placeholder="Rayon" readonly class="input-readonly" tabindex="-1">
                </div>
                <input id="provider-city-id" type="hidden" value="">
                <input id="provider-district-id" type="hidden" value="">
                <p class="muted text-xs">Şəhər və rayon xəritədən / ünvan axtarışından avtomatik doldurulur.</p>
                <div class="place-wrap">
                    <input id="provider-place-search" type="text" placeholder="Ünvan axtar (Google Places)">
                    <div id="provider-place-suggestions" class="suggestions"></div>
                </div>
                <div id="provider-map" class="map"></div>
                <input id="provider-lat" type="hidden" value="40.4093">
                <input id="provider-lng" type="hidden" value="49.8671">
                <div class="audio-intro">
                    <div class="audio-intro-head">
                        <div>
                            <p class="audio-intro-title">Audio intro</p>
                            <p class="audio-intro-hint muted">Qısa tanıtım — maks. 20 saniyə. Admin təsdiqinə kömək edir.</p>
                        </div>
                        <span id="audio-intro-timer" class="audio-intro-timer" hidden>00:00</span>
                    </div>
                    <audio id="provider-audio-player" class="audio-intro-player" controls hidden></audio>
                    <p id="audio-intro-status" class="audio-intro-status muted">Hələ audio yoxdur</p>
                    <div class="audio-intro-actions">
                        <button type="button" id="audio-record-btn" class="btn btn-primary btn-inline">
                            <span class="audio-btn-label">Mikrofonla yaz</span>
                        </button>
                        <button type="button" id="audio-pick-btn" class="btn btn-outline btn-inline">Fayl seç</button>
                    </div>
                    <input id="provider-audio-file" type="file" accept="audio/*,.m4a,.mp3,.wav,.webm,.ogg" hidden>
                </div>
                <button type="button" id="save-provider-profile" class="btn btn-dark">Profili yarat / yenilə</button>
            </div>
        </section>
    </div>

    <section class="card mt" data-role="provider" id="provider-list-panel" hidden>
        <h2>Mövcud profillər</h2>
        <div id="provider-list" class="stack muted">Hələ profil yoxdur</div>
    </section>
@endsection
