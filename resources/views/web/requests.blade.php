@extends('web.layout')

@section('title', 'MySancho · Sorğularım')
@section('page', 'requests')

@section('content')
    <header class="page-hero">
        <p class="eyebrow">Ailə</p>
        <h1>Sorğularım</h1>
        <p>Keçmiş və aktiv sorğular — birini aç, match nəticələrinə qayıt.</p>
    </header>

    <section id="role-gate-client" class="card card-hero" hidden>
        <h2>Bu səhifə ailə üçündür</h2>
        <p class="muted">İcraçı sorğu siyahısına baxmır. Gələn işlərə keçin.</p>
        <div class="actions mt">
            <a href="{{ route('web.jobs') }}" class="btn btn-primary btn-inline">Gələn işlər</a>
            <a href="{{ route('web.app') }}" class="btn btn-outline btn-inline">Ana səhifə</a>
        </div>
    </section>

    <section class="card" id="requests-panel" data-role="client">
        <div class="section-head">
            <h2>Tarixçə</h2>
            <div class="actions">
                <a href="{{ route('web.request') }}" class="btn btn-primary btn-inline">Yeni sorğu</a>
                <button type="button" id="refresh-requests" class="btn btn-outline btn-inline">Yenilə</button>
            </div>
        </div>
        <div id="requests-list" class="matches mt">Yüklənir…</div>
    </section>

    <section class="card card-log mt">
        <h2>Log</h2>
        <pre id="log"></pre>
    </section>
@endsection
