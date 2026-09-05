@extends('web.layout')

@section('title', 'My Sancho · '.wt('web.nav.request', 'Sorğu'))
@section('page', 'request')

@section('content')
    <header class="page-hero">
        <p class="eyebrow" data-i18n="web.role.client">{{ wt('web.role.client', 'Ailə') }}</p>
        <h1 id="request-page-title" data-i18n="web.nav.request">{{ wt('web.nav.request', 'Sorğu yarat') }}</h1>
        <p id="request-page-sub" data-i18n="web.request.subtitle">{{ wt('web.request.subtitle', 'Kateqoriya seç, əlavə qeyd yaz, ünvanı göstər — uyğun icraçılar çıxır.') }}</p>
        <p class="mt">
            <a href="{{ route('web.requests') }}" class="btn btn-outline btn-inline" data-i18n="web.nav.requests">{{ wt('web.nav.requests', 'Sorğularım') }}</a>
            <a href="{{ route('web.request') }}" class="btn btn-primary btn-inline" id="request-new-link" hidden data-i18n="web.dashboard.cta_new_request">{{ wt('web.dashboard.cta_new_request', 'Yeni sorğu') }}</a>
        </p>
    </header>

    <section id="role-gate-client" class="card card-hero" hidden>
        <h2 data-i18n="web.requests.gate_title">{{ wt('web.requests.gate_title', 'Bu səhifə ailə üçündür') }}</h2>
        <p class="muted" data-i18n="web.request.gate_body">{{ wt('web.request.gate_body', 'İcraçı sorğu yarada bilməz. Gələn işlərə baxın.') }}</p>
        <div class="actions mt">
            <a href="{{ route('web.jobs') }}" class="btn btn-primary btn-inline" data-i18n="web.nav.jobs">{{ wt('web.nav.jobs', 'Gələn işlər') }}</a>
            <a href="{{ route('web.app') }}" class="btn btn-outline btn-inline" data-i18n="web.nav.home">{{ wt('web.nav.home', 'Ana səhifə') }}</a>
        </div>
    </section>

    <div id="request-form" class="grid-main">
        <section class="card">
            <h2 data-i18n="web.dashboard.tile_profile_kicker">{{ wt('web.dashboard.tile_profile_kicker', 'Hesab') }}</h2>
            <p class="muted" data-i18n="web.request.role_locked">{{ wt('web.request.role_locked', 'Rol qeydiyyatda seçilir və dəyişdirilmir.') }}</p>
            <div class="stack">
                <p class="muted"><span data-i18n="web.request.active_role">{{ wt('web.request.active_role', 'Aktiv rol') }}</span>: <b id="request-role-label">—</b></p>
                <button type="button" id="logout" class="btn btn-danger" data-i18n="web.nav.logout">{{ wt('web.nav.logout', 'Çıxış') }}</button>
            </div>
        </section>

        <section class="card" id="request-editor">
            <h2 id="request-form-title" data-i18n="web.dashboard.cta_new_request">{{ wt('web.dashboard.cta_new_request', 'Yeni sorğu') }}</h2>
            <p class="muted" id="request-form-hint" data-i18n="web.request.form_hint">{{ wt('web.request.form_hint', 'Əvvəl kateqoriya seçin — əlavə qeyd və ünvan dəqiqləşdirir.') }}</p>

            <div class="request-mode-tabs" id="request-mode-tabs" role="tablist" aria-label="{{ wt('web.request.mode_aria', 'Sorğu yaratma üsulu') }}">
                <button type="button" class="request-mode-tab is-active" role="tab" aria-selected="true" data-mode="text" id="request-mode-text-tab" data-i18n="web.request.mode_text">
                    {{ wt('web.request.mode_text', 'Standart') }}
                </button>
                <button type="button" class="request-mode-tab" role="tab" aria-selected="false" data-mode="voice" id="request-mode-voice-tab" data-i18n="web.request.mode_voice">
                    {{ wt('web.request.mode_voice', 'Səsli') }}
                </button>
            </div>

            <div id="request-mode-text" class="request-mode-panel" role="tabpanel">
                <label class="field">
                    <span data-i18n="web.request.note">{{ wt('web.request.note', 'Əlavə qeyd (istəyə bağlı)') }}</span>
                    <input id="text" type="text" placeholder="{{ wt('web.request.note_ph', 'Məs: Nərimanovda sabah 2 saatlıq, təcrübəli olsun') }}" data-i18n-placeholder="web.request.note_ph">
                </label>
            </div>

            <div id="request-mode-voice" class="request-mode-panel" role="tabpanel" hidden>
                <p class="muted request-voice-lead" data-i18n="web.request.voice_hint">
                    {{ wt('web.request.voice_hint', 'Nə lazımdır, harada və nə vaxt — qısa danışın. AI kateqoriya və ünvanı çıxarır.') }}
                </p>
                <div class="request-voice-sample">
                    <p class="request-voice-sample-quote" id="request-voice-sample-text" data-i18n="web.request.voice_sample_text">
                        {{ wt('web.request.voice_sample_text', 'Nərimanovda 2 saatlıq it gəzdirən adam lazımdır.') }}
                    </p>
                    <button type="button" id="request-voice-sample-btn" class="btn btn-outline btn-inline request-voice-sample-btn">
                        <span data-i18n="web.request.voice_sample">{{ wt('web.request.voice_sample', 'Nümunə səs') }}</span>
                    </button>
                </div>
                <div class="request-voice-box">
                    <button type="button" id="request-voice-btn" class="btn btn-primary btn-inline request-voice-btn">
                        <span class="request-voice-btn-label" data-i18n="web.request.voice_record">{{ wt('web.request.voice_record', 'Mikrofonla yaz') }}</span>
                    </button>
                    <span id="request-voice-timer" class="request-voice-timer" hidden>00:00</span>
                </div>
                <p id="request-voice-status" class="muted mt text-xs" data-i18n="web.request.voice_idle">
                    {{ wt('web.request.voice_idle', 'Hazırsınızsa yazmağa başlayın (ən azı 3, maks. 20 san). Dayandıranda sorğu göndərilir.') }}
                </p>
            </div>

            <div id="request-manual-fields" class="form-row form-row-2 mt">
                <div class="span-2 field">
                    <span id="request-category-label" data-i18n="web.request.category">{{ wt('web.request.category', 'Kateqoriya') }}</span>
                    <div class="cat-picker" id="request-category-picker">
                        <input type="hidden" id="request-category" value="">
                        <input
                            id="request-category-search"
                            type="search"
                            autocomplete="off"
                            placeholder="{{ wt('web.request.category_search_ph', 'Axtar və ya kateqoriya seç…') }}"
                            data-i18n-placeholder="web.request.category_search_ph"
                            aria-autocomplete="list"
                            aria-controls="request-category-menu"
                            aria-expanded="false"
                        >
                        <div id="request-category-menu" class="cat-picker-menu" hidden role="listbox"></div>
                    </div>
                </div>
                <div class="span-2 place-wrap">
                    <input id="place-search" type="text" placeholder="{{ wt('web.request.place_ph', 'Ünvan axtar (Google Places)') }}" data-i18n-placeholder="web.request.place_ph">
                    <div id="place-suggestions" class="suggestions"></div>
                </div>
                <input id="lat" type="hidden" value="40.4093">
                <input id="lng" type="hidden" value="49.8671">
                <button type="button" id="create-request" class="btn btn-primary" data-i18n="web.request.create">{{ wt('web.request.create', 'Sorğu yarat') }}</button>
                <button type="button" id="refresh-request" class="btn btn-outline" data-i18n="web.request.refresh">{{ wt('web.request.refresh', 'Nəticələri yenilə') }}</button>
            </div>
            <p id="request-info" class="muted mt" data-i18n="web.request.none">{{ wt('web.request.none', 'Sorğu yoxdur') }}</p>
            <div id="request-audio-wrap" class="request-audio-wrap" hidden>
                <p class="provider-section-label" data-i18n="web.request.your_audio">{{ wt('web.request.your_audio', 'Səsiniz') }}</p>
                <audio id="request-audio" class="request-audio-player" controls preload="metadata"></audio>
                <p id="request-audio-fail" class="muted text-xs mt" hidden data-i18n="search.transcript_failed">
                    {{ wt('search.transcript_failed', 'Səs oxunmadı. Eyni mətni yazıb yenidən göndərin.') }}
                </p>
            </div>
            <div id="request-map-block">
                <div id="map" class="map mt"></div>
                <p id="place-label" class="muted mt" data-i18n="web.request.map_hint">{{ wt('web.request.map_hint', 'Google Map. Ünvan axtar və ya “Mənim yerim”.') }}</p>
            </div>
        </section>
    </div>

    <section class="card mt" id="request-results">
        <div class="section-head">
            <h2 data-i18n="web.request.results">{{ wt('web.request.results', 'Nəticələr') }}</h2>
            <span id="match-count" class="pill">0</span>
        </div>
        <p id="search-meta" class="muted" hidden></p>
        <p id="connect-hint" class="muted" hidden></p>
        <div id="matches" class="matches"></div>
    </section>
@endsection
