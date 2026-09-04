@extends('web.layout')

@section('title', 'My Sancho · '.wt('web.profile.provider_card_title', 'Xidmətçi profili'))
@section('page', 'provider-public')

@section('content')
    <header class="page-hero">
        <p class="eyebrow" data-i18n="web.provider_public.eyebrow">{{ wt('web.provider_public.eyebrow', 'İcraçı profili') }}</p>
        <h1 id="pp-name" data-i18n="web.loading">{{ wt('web.loading', 'Yüklənir…') }}</h1>
        <p id="pp-subtitle" class="muted" data-i18n="web.provider.subtitle">{{ wt('web.provider.subtitle', 'Ətraflı məlumat, cədvəl və CONNECT.') }}</p>
    </header>

    <section class="card card-narrow" id="pp-card">
        <div id="pp-body">
            <p class="muted" data-i18n="web.provider_public.loading">{{ wt('web.provider_public.loading', 'Profil yüklənir…') }}</p>
        </div>
        <div class="actions mt" id="pp-actions" hidden>
            <a href="{{ route('web.request') }}" class="btn btn-outline" id="pp-back" data-i18n="web.common.back">{{ wt('web.common.back', 'Geri') }}</a>
            <button type="button" class="btn btn-primary" id="pp-connect" hidden data-i18n="web.connect.cta">{{ wt('web.connect.cta', 'CONNECT') }}</button>
        </div>
    </section>

    <section class="card card-log mt">
        <h2 data-i18n="web.debug.log">{{ wt('web.debug.log', 'Log') }}</h2>
        <pre id="log"></pre>
    </section>
@endsection
