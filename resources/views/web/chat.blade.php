@extends('web.layout')

@section('title', 'MySancho · Chat')
@section('page', 'chat')

@section('content')
    <header class="page-hero">
        <p class="eyebrow">Mesajlar</p>
        <h1>Söhbətlər</h1>
        <p>CONNECT sonrası yazışmalar burada.</p>
    </header>

    <section class="card">
        <div class="section-head">
            <h2>Aktiv chat-lər</h2>
            <button type="button" id="refresh-chats" class="btn btn-outline btn-inline">Yenilə</button>
        </div>
        <div id="chat-list" class="stack muted">Yüklənir…</div>
    </section>

    <section class="card card-log mt">
        <h2>Log</h2>
        <pre id="log"></pre>
    </section>
@endsection
