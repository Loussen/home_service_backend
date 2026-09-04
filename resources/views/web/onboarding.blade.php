@extends('web.layout')

@section('title', 'My Sancho · '.wt('web.onboarding.heading', 'Onboarding'))
@section('page', 'onboarding')

@section('content')
    <header class="page-hero">
        <p class="eyebrow" data-i18n="web.onboarding.eyebrow">{{ wt('web.onboarding.eyebrow', 'Başlanğıc') }}</p>
        <h1 data-i18n="web.onboarding.heading">{{ wt('web.onboarding.heading', 'Onboarding') }}</h1>
        <p id="onb-hero-sub" data-i18n="web.onboarding.hero_sub_2">{{ wt('web.onboarding.hero_sub_2', 'Hesab və məkan — iki addım.') }}</p>
    </header>

    <section class="card card-narrow">
        <ol class="stepper" id="onb-stepper">
            <li class="active" data-step="0" data-i18n="web.onboarding.step_account">{{ wt('web.onboarding.step_account', '1. Hesab') }}</li>
            <li data-step="1" hidden data-i18n="web.onboarding.step_category">{{ wt('web.onboarding.step_category', '2. Kateqoriya') }}</li>
            <li data-step="2" data-i18n="web.onboarding.step_place">{{ wt('web.onboarding.step_place', '2. Məkan') }}</li>
        </ol>

        <div class="step-panel" data-step-panel="0">
            <div class="stack">
                <input id="onb-name" type="text" placeholder="{{ wt('web.profile.name_ph', 'Ad Soyad') }}" data-i18n-placeholder="web.profile.name_ph">
                <select id="onb-role">
                    <option value="client" data-i18n="web.onboarding.role_client">{{ wt('web.onboarding.role_client', 'Ailə (client)') }}</option>
                    <option value="provider" data-i18n="web.onboarding.role_provider">{{ wt('web.onboarding.role_provider', 'Xidmət göstərən (provider)') }}</option>
                </select>
                <p class="muted" data-i18n="web.onboarding.role_locked">{{ wt('web.onboarding.role_locked', 'Rol birdəfəlikdir — eyni nömrə yalnız ailə və ya yalnız icraçı ola bilər.') }}</p>
            </div>
        </div>

        <div class="step-panel hidden" data-step-panel="1">
            <p class="muted" data-i18n="web.onboarding.cats_hint">{{ wt('web.onboarding.cats_hint', 'Maksimum 3 kateqoriya seç (yalnız icraçı üçün).') }}</p>
            <div id="onb-category-list" class="chips mt"></div>
            <p class="muted mt"><span data-i18n="web.categories.selected">{{ wt('web.categories.selected', 'Seçilənlər:') }}</span> <span id="onb-selected-count">0</span>/3</p>
        </div>

        <div class="step-panel hidden" data-step-panel="2">
            <div class="place-wrap">
                <input id="onb-place-search" type="text" placeholder="{{ wt('web.request.place_ph', 'Ünvan axtar (Google Places)') }}" data-i18n-placeholder="web.request.place_ph">
                <div id="onb-place-suggestions" class="suggestions"></div>
            </div>
            <div id="onb-map" class="map mt"></div>
            <div class="form-row form-row-2 mt">
                <input id="onb-lat" type="hidden" value="40.4093">
                <input id="onb-lng" type="hidden" value="49.8671">
            </div>
            <p id="onb-place-label" class="muted mt" data-i18n="web.onboarding.map_hint">{{ wt('web.onboarding.map_hint', 'Xəritədə klik et və ya ünvan axtar.') }}</p>
        </div>

        <div class="actions mt">
            <button type="button" id="onb-back" class="btn btn-outline" data-i18n="web.common.back">{{ wt('web.common.back', 'Geri') }}</button>
            <button type="button" id="onb-next" class="btn btn-primary" data-i18n="web.login.continue">{{ wt('web.login.continue', 'Davam et') }}</button>
        </div>
    </section>

    <section class="card card-log mt">
        <h2 data-i18n="web.debug.log">{{ wt('web.debug.log', 'Log') }}</h2>
        <pre id="log"></pre>
    </section>
@endsection
