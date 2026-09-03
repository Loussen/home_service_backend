@extends('web.layout')

@section('title', 'My Sancho · Jobs')
@section('page', 'jobs')

@section('content')
    <header class="page-hero">
        <p class="eyebrow" data-i18n="web.role.provider">İcraçı</p>
        <h1 data-i18n="web.nav.jobs">Gələn işlər</h1>
        <p data-i18n="web.jobs.subtitle">Sizə uyğun sorğular — cavab ver, chat aç, təklif göndər.</p>
    </header>

    <section id="role-gate-provider" class="card card-hero" hidden>
        <h2 data-i18n="web.jobs.gate_title">Bu səhifə icraçı üçündür</h2>
        <p class="muted" data-i18n="web.jobs.gate_body">Ailə gələn işlərə baxmır. Sorğu yaratmaq üçün ailə hesabı lazımdır.</p>
        <div class="actions mt">
            <a href="{{ route('web.request') }}" class="btn btn-primary btn-inline" data-i18n="web.nav.request">Sorğu yarat</a>
            <a href="{{ route('web.app') }}" class="btn btn-outline btn-inline" data-i18n="web.nav.home">Ana səhifə</a>
        </div>
    </section>

    <section class="card" id="jobs-panel" data-role="provider">
        <div class="section-head">
            <h2 data-i18n="web.common.list">Siyahı</h2>
            <button type="button" id="refresh-jobs" class="btn btn-outline btn-inline" data-i18n="web.common.refresh">Yenilə</button>
        </div>
        <div id="jobs-list" class="matches mt" data-i18n="web.loading">Yüklənir…</div>
    </section>

    <section class="card card-log mt">
        <h2>Log</h2>
        <pre id="log"></pre>
    </section>
@endsection
