@extends('web.layout')

@section('title', 'MySancho · İşlər')
@section('page', 'jobs')

@section('content')
    <header class="page-hero">
        <p class="eyebrow">İcraçı</p>
        <h1>Gələn işlər</h1>
        <p>Sizə uyğun sorğular — cavab ver, chat aç, təklif göndər.</p>
    </header>

    <section id="role-gate-provider" class="card card-hero" hidden>
        <h2>Bu səhifə icraçı üçündür</h2>
        <p class="muted">Ailə gələn işlərə baxmır. Sorğu yaratmaq üçün ailə rolunda olun.</p>
        <div class="actions mt">
            <a href="{{ route('web.request') }}" class="btn btn-primary btn-inline">Sorğu yarat</a>
            <button type="button" id="switch-to-provider" class="btn btn-outline btn-inline">İcraçı roluna keç</button>
        </div>
    </section>

    <section class="card" id="jobs-panel" data-role="provider">
        <div class="section-head">
            <h2>Siyahı</h2>
            <button type="button" id="refresh-jobs" class="btn btn-outline btn-inline">Yenilə</button>
        </div>
        <div id="jobs-list" class="matches mt">Yüklənir…</div>
    </section>

    <section class="card card-log mt">
        <h2>Log</h2>
        <pre id="log"></pre>
    </section>
@endsection
