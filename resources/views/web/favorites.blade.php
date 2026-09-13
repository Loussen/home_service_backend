@extends('web.layout')

@section('title', 'My Sancho · '.wt('web.nav.favorites', 'Seçilmişlər'))
@section('page', 'favorites')

@section('content')
    <header class="page-hero">
        <p class="eyebrow" data-i18n="account.menu.favorites">{{ wt('account.menu.favorites', 'Seçilmişlər') }}</p>
        <h1 data-i18n="favorites.title">{{ wt('favorites.title', 'Seçilmişlər') }}</h1>
        <p data-i18n="web.favorites.subtitle">{{ wt('web.favorites.subtitle', 'Saxladığınız xidmətçi profilləri.') }}</p>
    </header>

    <section class="card">
        <div class="section-head">
            <h2 data-i18n="favorites.title">{{ wt('favorites.title', 'Seçilmişlər') }}</h2>
            <button type="button" id="refresh-favorites" class="btn btn-outline btn-inline" data-i18n="web.common.refresh">Yenilə</button>
        </div>
        <div id="favorites-list" class="stack muted" data-i18n="web.loading">Yüklənir…</div>
    </section>
@endsection
