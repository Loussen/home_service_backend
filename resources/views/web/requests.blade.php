@extends('web.layout')

@section('title', 'My Sancho · Sorğularım')
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

    <section class="card requests-panel" id="requests-panel" data-role="client">
        <div class="section-head requests-toolbar">
            <div>
                <h2>Tarixçə</h2>
                <p class="requests-toolbar-hint muted">Son sorğulardan match nəticəsinə qayıt</p>
            </div>
            <div class="actions">
                <a href="{{ route('web.request') }}" class="btn btn-primary btn-inline">Yeni sorğu</a>
                <button type="button" id="refresh-requests" class="btn btn-outline btn-inline">Yenilə</button>
            </div>
        </div>
        <div id="requests-list" class="request-history">Yüklənir…</div>
    </section>
@endsection
