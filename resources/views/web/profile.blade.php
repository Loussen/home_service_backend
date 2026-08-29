@extends('web.layout')

@section('title', 'MySancho · Profil')
@section('page', 'profile')

@section('content')
    <header class="page-hero">
        <p class="eyebrow">Hesab</p>
        <h1>Profil</h1>
        <p>Şəxsi məlumat və xidmətçi profili — məkan, bio, kateqoriyalar.</p>
    </header>

    <div class="grid-main">
        <section class="card">
            <h2>İstifadəçi</h2>
            <div class="stack">
                <input id="profile-name" type="text" placeholder="Ad Soyad">
                <input id="profile-avatar" type="text" placeholder="Avatar URL">
                <button type="button" id="save-profile" class="btn btn-primary">Profili yenilə</button>
            </div>
        </section>

        <section class="card">
            <h2>Xidmətçi profili</h2>
            <p class="muted">Kateqoriyalar səhifəsində seçdiyin tag-lər burada istifadə olunur.</p>
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
                <input id="provider-lat" type="text" placeholder="Latitude" value="40.4093">
                <input id="provider-lng" type="text" placeholder="Longitude" value="49.8671">
                <button type="button" id="save-provider-profile" class="btn btn-dark">Profili yarat / yenilə</button>
            </div>
        </section>
    </div>

    <section class="card mt">
        <h2>Mövcud profillər</h2>
        <div id="provider-list" class="stack muted">Hələ profil yoxdur</div>
    </section>

    <section class="card card-log mt">
        <h2>Log</h2>
        <pre id="log"></pre>
    </section>
@endsection
