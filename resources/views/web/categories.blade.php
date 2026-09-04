@extends('web.layout')

@section('title', 'My Sancho · '.wt('web.nav.categories', 'Kateqoriyalar'))
@section('page', 'categories')

@section('content')
    <header class="page-hero">
        <p class="eyebrow" data-i18n="web.role.provider">{{ wt('web.role.provider', 'İcraçı') }}</p>
        <h1 data-i18n="web.nav.categories">{{ wt('web.nav.categories', 'Kateqoriyalar') }}</h1>
        <p data-i18n="web.categories.subtitle">{{ wt('web.categories.subtitle', 'Maksimum 3 yarpaq kateqoriya seç — profil yaradanda istifadə olunur.') }}</p>
    </header>

    <section class="card">
        <div id="category-list" class="chips"></div>
        <p class="muted mt">
            <span data-i18n="web.categories.selected">{{ wt('web.categories.selected', 'Seçilənlər:') }}</span>
            <span id="selected-count">0</span>/3
        </p>
        <div class="actions mt">
            <button type="button" id="save-categories" class="btn btn-primary" data-i18n="web.categories.save">{{ wt('web.categories.save', 'Seçimi yadda saxla') }}</button>
        </div>
    </section>

    <section class="card card-log mt">
        <h2 data-i18n="web.debug.log">{{ wt('web.debug.log', 'Log') }}</h2>
        <pre id="log"></pre>
    </section>
@endsection
