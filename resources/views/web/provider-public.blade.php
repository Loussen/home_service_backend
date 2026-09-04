@extends('web.layout')

@section('title', 'My Sancho · '.wt('web.profile.provider_card_title', 'Xidmətçi profili'))
@section('page', 'provider-public')

@section('content')
    <header class="page-hero page-hero-compact">
        <p class="eyebrow" data-i18n="web.provider_public.eyebrow">{{ wt('web.provider_public.eyebrow', 'İcraçı profili') }}</p>
        <h1 id="pp-name" class="sr-only" data-i18n="web.loading">{{ wt('web.loading', 'Yüklənir…') }}</h1>
        <p id="pp-subtitle" class="muted" hidden></p>
    </header>

    <section class="card card-provider" id="pp-card">
        <div id="pp-body" class="provider-public-body">
            <p class="muted" data-i18n="web.provider_public.loading">{{ wt('web.provider_public.loading', 'Profil yüklənir…') }}</p>
        </div>
        <div class="provider-public-actions" id="pp-actions" hidden>
            <a href="{{ route('web.request') }}" class="btn btn-outline" id="pp-back" data-i18n="web.common.back">{{ wt('web.common.back', 'Geri') }}</a>
            <button type="button" class="btn btn-primary" id="pp-connect" hidden data-i18n="web.connect.cta">{{ wt('web.connect.cta', 'CONNECT') }}</button>
        </div>
    </section>
@endsection
