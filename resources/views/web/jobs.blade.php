@extends('web.layout')

@section('title', 'MySancho Web · İşlər')
@section('page', 'jobs')

@section('content')
    <section class="card">
        <div class="section-head">
            <h2>Gələn işlər</h2>
            <button type="button" id="refresh-jobs" class="btn btn-outline btn-inline">Yenilə</button>
        </div>
        <p class="muted">Bu səhifə icraçı (provider) üçündür. Rol avtomatik dəyişdiriləcək.</p>
        <div id="jobs-list" class="matches mt">Yüklənir…</div>
    </section>

    <section class="card mt">
        <h2>Log</h2>
        <pre id="log"></pre>
    </section>
@endsection
