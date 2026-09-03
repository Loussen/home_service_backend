@extends('web.layout')

@section('title', 'MySancho · Onboarding')
@section('page', 'onboarding')

@section('content')
    <header class="page-hero">
        <p class="eyebrow">Başlanğıc</p>
        <h1>Onboarding</h1>
        <p id="onb-hero-sub">Hesab və məkan — iki addım.</p>
    </header>

    <section class="card card-narrow">
        <ol class="stepper" id="onb-stepper">
            <li class="active" data-step="0">1. Hesab</li>
            <li data-step="1" hidden>2. Kateqoriya</li>
            <li data-step="2">2. Məkan</li>
        </ol>

        <div class="step-panel" data-step-panel="0">
            <div class="stack">
                <input id="onb-name" type="text" placeholder="Ad Soyad">
                <select id="onb-role">
                    <option value="client">Ailə (client)</option>
                    <option value="provider">Xidmət göstərən (provider)</option>
                </select>
                <p class="muted">Rol birdəfəlikdir — eyni nömrə yalnız ailə və ya yalnız icraçı ola bilər.</p>
            </div>
        </div>

        <div class="step-panel hidden" data-step-panel="1">
            <p class="muted">Maksimum 3 kateqoriya seç (yalnız icraçı üçün).</p>
            <div id="onb-category-list" class="chips mt"></div>
            <p class="muted mt">Seçilənlər: <span id="onb-selected-count">0</span>/3</p>
        </div>

        <div class="step-panel hidden" data-step-panel="2">
            <div class="place-wrap">
                <input id="onb-place-search" type="text" placeholder="Ünvan axtar (Google Places)">
                <div id="onb-place-suggestions" class="suggestions"></div>
            </div>
            <div id="onb-map" class="map mt"></div>
            <div class="form-row form-row-2 mt">
                <input id="onb-lat" type="hidden" value="40.4093">
                <input id="onb-lng" type="hidden" value="49.8671">
            </div>
            <p id="onb-place-label" class="muted mt">Xəritədə klik et və ya ünvan axtar.</p>
        </div>

        <div class="actions mt">
            <button type="button" id="onb-back" class="btn btn-outline">Geri</button>
            <button type="button" id="onb-next" class="btn btn-primary">Davam et</button>
        </div>
    </section>

    <section class="card card-log mt">
        <h2>Log</h2>
        <pre id="log"></pre>
    </section>
@endsection
