@extends('web.layout')

@section('title', 'MySancho · Xidmətçi profili')
@section('page', 'provider-public')

@section('content')
    <header class="page-hero">
        <p class="eyebrow">İcraçı profili</p>
        <h1 id="pp-name">Yüklənir…</h1>
        <p id="pp-subtitle" class="muted">Ətraflı məlumat, cədvəl və CONNECT.</p>
    </header>

    <section class="card card-narrow" id="pp-card">
        <div id="pp-body">
            <p class="muted">Profil yüklənir…</p>
        </div>
        <div class="actions mt" id="pp-actions" hidden>
            <a href="{{ route('web.request') }}" class="btn btn-outline" id="pp-back">Geri</a>
            <button type="button" class="btn btn-primary" id="pp-connect" hidden>CONNECT</button>
        </div>
    </section>

    <section class="card card-log mt">
        <h2>Log</h2>
        <pre id="log"></pre>
    </section>
@endsection
