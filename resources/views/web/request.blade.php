@extends('web.layout')

@section('title', 'MySancho · Sorğu')
@section('page', 'request')

@section('content')
    <header class="page-hero">
        <p class="eyebrow">Ailə · Client</p>
        <h1 id="request-page-title">Sorğu yarat</h1>
        <p id="request-page-sub">Nə lazımdır, harada və nə vaxt — yaz, uyğun xidmətçiləri gör və CONNECT et.</p>
        <p class="mt">
            <a href="{{ route('web.requests') }}" class="btn btn-outline btn-inline">Sorğularım</a>
            <a href="{{ route('web.request') }}" class="btn btn-primary btn-inline" id="request-new-link" hidden>Yeni sorğu</a>
        </p>
    </header>

    <section id="role-gate-client" class="card card-hero" hidden>
        <h2>Bu səhifə ailə üçündür</h2>
        <p class="muted">İcraçı sorğu yarada bilməz. Gələn işlərə baxın.</p>
        <div class="actions mt">
            <a href="{{ route('web.jobs') }}" class="btn btn-primary btn-inline">Gələn işlər</a>
            <a href="{{ route('web.app') }}" class="btn btn-outline btn-inline">Ana səhifə</a>
        </div>
    </section>

    <div id="request-form" class="grid-main">
        <section class="card">
            <h2>Hesab</h2>
            <p class="muted">Rol qeydiyyatda seçilir və dəyişdirilmir.</p>
            <div class="stack">
                <p class="muted">Aktiv rol: <b id="request-role-label">—</b></p>
                <button type="button" id="logout" class="btn btn-danger">Çıxış</button>
            </div>
        </section>

        <section class="card" id="request-editor">
            <h2 id="request-form-title">Yeni sorğu</h2>
            <p class="muted" id="request-form-hint">Mətn və ünvan — xəritədə dəqiq yer seçə bilərsiniz.</p>
            <div class="form-row form-row-2">
                <input id="text" class="span-2" type="text" placeholder="Məs: Nərimanovda sabah 2 saatlıq it gəzdirmə lazımdır">
                <div class="span-2 place-wrap">
                    <input id="place-search" type="text" placeholder="Ünvan axtar (Google Places)">
                    <div id="place-suggestions" class="suggestions"></div>
                </div>
                <input id="lat" type="text" placeholder="Latitude" value="40.4093">
                <input id="lng" type="text" placeholder="Longitude" value="49.8671">
                <button type="button" id="create-request" class="btn btn-primary">Sorğu yarat</button>
                <button type="button" id="refresh-request" class="btn btn-outline">Nəticələri yenilə</button>
            </div>
            <p id="request-info" class="muted mt">Sorğu yoxdur</p>
            <div id="map" class="map mt"></div>
            <p id="place-label" class="muted mt">Google Map. Ünvan axtar və ya “Mənim yerim”.</p>
        </section>
    </div>

    <section class="card mt" id="request-results">
        <div class="section-head">
            <h2>Nəticələr</h2>
            <span id="match-count" class="pill">0 nəticə</span>
        </div>
        <p id="connect-hint" class="muted" hidden></p>
        <div id="matches" class="matches"></div>
    </section>
@endsection
