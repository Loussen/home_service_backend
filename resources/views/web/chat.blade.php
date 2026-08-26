@extends('web.layout')

@section('title', 'MySancho Web · Chat')
@section('page', 'chat')

@section('content')
    <section class="card">
        <div class="section-head">
            <h2>Söhbətlər</h2>
            <button type="button" id="refresh-chats" class="btn btn-outline btn-inline">Yenilə</button>
        </div>
        <div id="chat-list" class="stack muted">Yüklənir…</div>
    </section>

    <section class="card mt">
        <h2>Log</h2>
        <pre id="log"></pre>
    </section>
@endsection
