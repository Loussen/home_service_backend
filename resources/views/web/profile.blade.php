@extends('web.layout')

@section('title', 'My Sancho · '.wt('web.nav.profile', 'Profil'))
@section('page', 'profile')

@section('content')
    <header class="page-hero">
        <p class="eyebrow" data-i18n="web.dashboard.tile_profile_kicker">{{ wt('web.dashboard.tile_profile_kicker', 'Hesab') }}</p>
        <h1 data-i18n="web.nav.profile">{{ wt('web.nav.profile', 'Profil') }}</h1>
        <p id="profile-hero-sub" data-role="client" hidden data-i18n="web.profile.hero_client">{{ wt('web.profile.hero_client', 'Ad, şəkil, balans — ailə hesabı.') }}</p>
        <p id="profile-hero-sub-provider" data-role="provider" hidden data-i18n="web.profile.hero_provider">{{ wt('web.profile.hero_provider', 'Şəxsi məlumat, şəkil, audio və xidmətçi profili.') }}</p>
    </header>

    <section class="card card-hero approval-banner" id="provider-pending-banner" hidden>
        <h2 id="provider-pending-title" data-i18n="web.profile.pending_title">{{ wt('web.profile.pending_title', 'Təsdiq gözlənilir') }}</h2>
        <p id="provider-pending-text" class="muted" data-i18n="web.profile.pending_body">
            {{ wt('web.profile.pending_body', 'Sorğunuz 1 saat ərzində baxılacaq. Təsdiqləndikdən sonra iş sorğuları gələcək.') }}
        </p>
    </section>

    <div class="grid-main">
        <section class="card">
            <h2 data-i18n="web.auth.user">{{ wt('web.auth.user', 'İstifadəçi') }}</h2>
            <div class="stack">
                <div class="avatar-upload">
                    <div class="avatar-shell">
                        <button type="button" id="avatar-pick" class="avatar-frame" aria-label="{{ wt('web.profile.avatar_aria', 'Profil şəkli seç') }}" data-i18n-aria="web.profile.avatar_aria">
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
                        <p class="avatar-upload-title" data-i18n="web.profile.avatar_title">{{ wt('web.profile.avatar_title', 'Profil şəkli') }}</p>
                        <p class="avatar-upload-hint muted" data-i18n="web.profile.avatar_hint">{{ wt('web.profile.avatar_hint', 'JPG, PNG və ya WebP · maks. 5 MB') }}</p>
                        <button type="button" id="upload-avatar" class="btn-link-brand" data-i18n="web.profile.avatar_pick">{{ wt('web.profile.avatar_pick', 'Şəkil seç / dəyiş') }}</button>
                    </div>
                    <input id="profile-avatar-file" type="file" accept="image/jpeg,image/png,image/webp" hidden>
                </div>
                <input id="profile-name" type="text" placeholder="{{ wt('web.profile.name_ph', 'Ad Soyad') }}" data-i18n-placeholder="web.profile.name_ph">
                <p class="muted"><span data-i18n="web.profile.role_label">{{ wt('web.profile.role_label', 'Rol') }}</span>: <b id="profile-role-label">—</b> · <span data-i18n="web.profile.balance_label">{{ wt('web.profile.balance_label', 'Balans') }}</span>: <b id="profile-balance">—</b></p>
                <p class="muted" id="profile-approval-label" hidden></p>
                <button type="button" id="save-profile" class="btn btn-primary" data-i18n="web.profile.save_user">{{ wt('web.profile.save_user', 'Profili yenilə') }}</button>
            </div>
        </section>

        <section class="card" data-role="client" id="client-profile-panel" hidden>
            <h2 data-i18n="web.profile.client_card_title">{{ wt('web.profile.client_card_title', 'Ailə hesabı') }}</h2>
            <p class="muted" data-i18n="web.profile.client_card_body">{{ wt('web.profile.client_card_body', 'Xidmət axtarır, CONNECT edir və chat-də razılaşırsınız.') }}</p>
            <div class="stack mt">
                <a href="{{ route('web.request') }}" class="btn btn-primary" data-i18n="web.dashboard.cta_new_request">{{ wt('web.dashboard.cta_new_request', 'Yeni sorğu') }}</a>
                <a href="{{ route('web.requests') }}" class="btn btn-outline" data-i18n="web.nav.requests">{{ wt('web.nav.requests', 'Sorğularım') }}</a>
                <a href="{{ route('web.chat') }}" class="btn btn-outline" data-i18n="web.dashboard.cta_chats">{{ wt('web.dashboard.cta_chats', 'Söhbətlər') }}</a>
                <a href="{{ route('web.app') }}" class="btn btn-outline" data-i18n="web.nav.home">{{ wt('web.nav.home', 'Ana səhifə') }}</a>
            </div>
        </section>

        <section class="card" data-role="provider" id="provider-profile-panel" hidden>
            <h2 data-i18n="web.profile.provider_card_title">{{ wt('web.profile.provider_card_title', 'Xidmətçi profili') }}</h2>
            <p class="muted" data-i18n="web.profile.provider_card_body">{{ wt('web.profile.provider_card_body', 'Audio intro admin təsdiqinə kömək edir (maks. 20 san). Kateqoriyaları aşağıdakı accordion-dan dəyişə bilərsiniz.') }}</p>

            <details class="profile-accordion" id="profile-categories-accordion">
                <summary class="profile-accordion-summary">
                    <span class="profile-accordion-title" data-i18n="web.profile.cats_accordion">{{ wt('web.profile.cats_accordion', 'Kateqoriyalarını dəyiş') }}</span>
                    <span class="profile-accordion-meta" id="profile-cat-summary">0/3</span>
                </summary>
                <div class="profile-accordion-body">
                    <p class="muted text-xs mb-2" data-i18n="web.profile.cats_hint">{{ wt('web.profile.cats_hint', 'Maksimum 3 yarpaq kateqoriya. Qrupu açaraq seçin.') }}</p>
                    <div id="profile-category-groups" class="category-accordion-groups"></div>
                    <p class="muted mt"><span data-i18n="web.categories.selected">{{ wt('web.categories.selected', 'Seçilənlər:') }}</span> <span id="profile-selected-count">0</span>/3</p>
                    <button type="button" id="save-profile-categories" class="btn btn-outline mt" data-i18n="web.profile.cats_save">{{ wt('web.profile.cats_save', 'Kateqoriyaları saxla') }}</button>
                </div>
            </details>

            <div class="stack mt">
                <input id="provider-title" type="text" placeholder="{{ wt('web.profile.title_ph', 'Başlıq (məs: Uşaq baxıcısı)') }}" data-i18n-placeholder="web.profile.title_ph">
                <textarea id="provider-bio" rows="4" placeholder="{{ wt('web.profile.bio_ph', 'Qısa bio') }}" data-i18n-placeholder="web.profile.bio_ph"></textarea>
                <div class="form-row form-row-2">
                    <input id="provider-city" type="text" placeholder="{{ wt('web.profile.city_ph', 'Şəhər') }}" data-i18n-placeholder="web.profile.city_ph" readonly class="input-readonly" tabindex="-1">
                    <input id="provider-district" type="text" placeholder="{{ wt('web.profile.district_ph', 'Rayon') }}" data-i18n-placeholder="web.profile.district_ph" readonly class="input-readonly" tabindex="-1">
                </div>
                <input id="provider-city-id" type="hidden" value="">
                <input id="provider-district-id" type="hidden" value="">
                <p class="muted text-xs" data-i18n="web.profile.place_auto_hint">{{ wt('web.profile.place_auto_hint', 'Şəhər və rayon xəritədən / ünvan axtarışından avtomatik doldurulur.') }}</p>
                <div class="place-wrap">
                    <input id="provider-place-search" type="text" placeholder="{{ wt('web.request.place_ph', 'Ünvan axtar (Google Places)') }}" data-i18n-placeholder="web.request.place_ph">
                    <div id="provider-place-suggestions" class="suggestions"></div>
                </div>
                <div id="provider-map" class="map"></div>
                <input id="provider-lat" type="hidden" value="40.4093">
                <input id="provider-lng" type="hidden" value="49.8671">
                <div class="audio-intro">
                    <div class="audio-intro-head">
                        <div>
                            <p class="audio-intro-title" data-i18n="web.profile.audio_title">{{ wt('web.profile.audio_title', 'Audio intro') }}</p>
                            <p class="audio-intro-hint muted" data-i18n="web.profile.audio_hint">{{ wt('web.profile.audio_hint', 'Qısa tanıtım — maks. 20 saniyə. Admin təsdiqinə kömək edir.') }}</p>
                        </div>
                        <span id="audio-intro-timer" class="audio-intro-timer" hidden>00:00</span>
                    </div>
                    <audio id="provider-audio-player" class="audio-intro-player" controls hidden></audio>
                    <p id="audio-intro-status" class="audio-intro-status muted" data-i18n="web.profile.audio_empty">{{ wt('web.profile.audio_empty', 'Hələ audio yoxdur') }}</p>
                    <div class="audio-intro-actions">
                        <button type="button" id="audio-record-btn" class="btn btn-primary btn-inline">
                            <span class="audio-btn-label" data-i18n="web.profile.audio_record">{{ wt('web.profile.audio_record', 'Mikrofonla yaz') }}</span>
                        </button>
                        <button type="button" id="audio-pick-btn" class="btn btn-outline btn-inline" data-i18n="web.profile.audio_pick">{{ wt('web.profile.audio_pick', 'Fayl seç') }}</button>
                    </div>
                    <input id="provider-audio-file" type="file" accept="audio/*,.m4a,.mp3,.wav,.webm,.ogg" hidden>
                </div>
                <button type="button" id="save-provider-profile" class="btn btn-dark" data-i18n="web.profile.save_provider">{{ wt('web.profile.save_provider', 'Profili yarat / yenilə') }}</button>
            </div>
        </section>
    </div>
@endsection
