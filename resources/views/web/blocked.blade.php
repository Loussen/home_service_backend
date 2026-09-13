@extends('web.layout')

@section('title', 'My Sancho · '.wt('web.nav.blocked', 'Bloklanmışlar'))
@section('page', 'blocked')

@section('content')
    <header class="page-hero">
        <p class="eyebrow" data-i18n="web.chat.eyebrow">{{ wt('web.chat.eyebrow', 'Mesajlar') }}</p>
        <h1 data-i18n="block.list_title">{{ wt('block.list_title', 'Bloklanmışlar') }}</h1>
        <p data-i18n="web.blocked.subtitle">{{ wt('web.blocked.subtitle', 'Blokladığınız istifadəçilər — bloku götürə bilərsiniz.') }}</p>
    </header>

    <section class="card">
        <div class="section-head">
            <h2 data-i18n="block.list_title">{{ wt('block.list_title', 'Bloklanmışlar') }}</h2>
            <button type="button" id="refresh-blocked" class="btn btn-outline btn-inline" data-i18n="web.common.refresh">Yenilə</button>
        </div>
        <div id="blocked-list" class="stack muted" data-i18n="web.loading">Yüklənir…</div>
    </section>
@endsection
