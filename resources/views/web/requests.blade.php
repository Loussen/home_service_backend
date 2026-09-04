@extends('web.layout')

@section('title', 'My Sancho · My requests')
@section('page', 'requests')

@section('content')
    <header class="page-hero">
        <p class="eyebrow" data-i18n="web.role.client">Ailə</p>
        <h1 data-i18n="web.nav.requests">Sorğularım</h1>
        <p data-i18n="web.requests.subtitle">Keçmiş və aktiv sorğular — birini aç, match nəticələrinə qayıt.</p>
    </header>

    <section id="role-gate-client" class="card card-hero" hidden>
        <h2 data-i18n="web.requests.gate_title">Bu səhifə ailə üçündür</h2>
        <p class="muted" data-i18n="web.requests.gate_body">İcraçı sorğu siyahısına baxmır. Gələn işlərə keçin.</p>
        <div class="actions mt">
            <a href="{{ route('web.jobs') }}" class="btn btn-primary btn-inline" data-i18n="web.nav.jobs">Gələn işlər</a>
            <a href="{{ route('web.app') }}" class="btn btn-outline btn-inline" data-i18n="web.nav.home">Ana səhifə</a>
        </div>
    </section>

    <section class="card requests-panel" id="requests-panel" data-role="client">
        <div class="section-head requests-toolbar">
            <div>
                <h2 data-i18n="web.requests.history">Tarixçə</h2>
                <p class="requests-toolbar-hint muted" data-i18n="web.requests.history_hint">Son sorğulardan match nəticəsinə qayıt</p>
            </div>
            <div class="actions">
                <a href="{{ route('web.request') }}" class="btn btn-primary btn-inline" data-i18n="web.dashboard.cta_new_request">Yeni sorğu</a>
                <button type="button" id="refresh-requests" class="btn btn-outline btn-inline" data-i18n="web.common.refresh">Yenilə</button>
            </div>
        </div>
        <div class="request-filter-tabs" id="requests-filter" role="tablist" aria-label="{{ wt('web.requests.filter_aria', 'Sorğu filteri') }}">
            <button type="button" class="request-filter-tab is-active" data-filter="all" role="tab" aria-selected="true" data-i18n="web.requests.filter_all">
                {{ wt('web.requests.filter_all', 'Hamısı') }}
            </button>
            <button type="button" class="request-filter-tab" data-filter="matched" role="tab" aria-selected="false" data-i18n="web.requests.filter_matched">
                {{ wt('web.requests.filter_matched', 'Uyğunlaşan') }}
            </button>
            <button type="button" class="request-filter-tab" data-filter="unmatched" role="tab" aria-selected="false" data-i18n="web.requests.filter_unmatched">
                {{ wt('web.requests.filter_unmatched', 'Uyğunlaşmayan') }}
            </button>
        </div>
        <div id="requests-list" class="request-history" data-i18n="web.loading">Yüklənir…</div>
        <div id="requests-pagination" class="requests-pagination" hidden></div>
    </section>
@endsection
