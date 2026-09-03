@extends('web.layout')

@section('title', 'My Sancho · Chat')
@section('page', 'chat')

@section('content')
    <header class="page-hero">
        <p class="eyebrow" data-i18n="web.dashboard.tile_chat_kicker">Mesajlar</p>
        <h1 data-i18n="web.dashboard.cta_chats">Söhbətlər</h1>
        <p data-i18n="web.chat.subtitle">CONNECT sonrası yazışmalar burada.</p>
    </header>

    <section class="card">
        <div class="section-head">
            <h2 data-i18n="web.chat.active">Aktiv chat-lər</h2>
            <button type="button" id="refresh-chats" class="btn btn-outline btn-inline" data-i18n="web.common.refresh">Yenilə</button>
        </div>
        <div id="chat-list" class="stack muted" data-i18n="web.loading">Yüklənir…</div>
    </section>

    <section class="card card-log mt">
        <h2>Log</h2>
        <pre id="log"></pre>
    </section>
@endsection
