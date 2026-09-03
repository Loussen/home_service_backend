@extends('web.layout')

@section('title', 'MySancho · Profil')
@section('page', 'profile')

@section('content')
    <header class="page-hero">
        <p class="eyebrow">Hesab</p>
        <h1>Profil</h1>
        <p id="profile-hero-sub" data-role="client">Ad, şəkil, balans — ailə hesabı.</p>
        <p id="profile-hero-sub-provider" data-role="provider" hidden>Şəxsi məlumat, şəkil, audio və xidmətçi profili.</p>
    </header>

    <section class="card card-hero" id="provider-pending-banner" hidden>
        <h2>Təsdiq gözlənilir</h2>
        <p id="provider-pending-text" class="muted">
            Sorğunuz 1 saat ərzində baxılacaq. Təsdiqləndikdən sonra iş sorğuları gələcək.
        </p>
    </section>

    <div class="grid-main">
        <section class="card">
            <h2>İstifadəçi</h2>
            <div class="stack">
                <div class="avatar-upload">
                    <img id="profile-avatar-img" class="avatar-preview" alt="" hidden>
                    <input id="profile-avatar-file" type="file" accept="image/jpeg,image/png,image/webp">
                    <button type="button" id="upload-avatar" class="btn btn-outline btn-inline">Şəkil yüklə</button>
                </div>
                <input id="profile-name" type="text" placeholder="Ad Soyad">
                <p class="muted">Rol: <b id="profile-role-label">—</b> · Balans: <b id="profile-balance">—</b></p>
                <p class="muted" id="profile-approval-label" hidden></p>
                <button type="button" id="save-profile" class="btn btn-primary">Profili yenilə</button>
            </div>
        </section>

        <section class="card" data-role="client" id="client-profile-panel">
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
                <input id="provider-city" type="text" placeholder="Şəhər">
                <input id="provider-district" type="text" placeholder="Rayon">
                <div class="place-wrap">
                    <input id="provider-place-search" type="text" placeholder="Ünvan axtar (Google Places)">
                    <div id="provider-place-suggestions" class="suggestions"></div>
                </div>
                <div id="provider-map" class="map"></div>
                <input id="provider-lat" type="hidden" value="40.4093">
                <input id="provider-lng" type="hidden" value="49.8671">
                <div>
                    <p class="muted">Audio intro (maks. 20 saniyə)</p>
                    <input id="provider-audio-file" type="file" accept="audio/*,.m4a,.mp3,.wav">
                    <audio id="provider-audio-player" class="mt" controls hidden></audio>
                    <button type="button" id="upload-provider-audio" class="btn btn-outline btn-inline mt">Audio yüklə</button>
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
