@extends('web.layout')

@section('title', 'My Sancho · Sign in')
@section('page', 'login')

@section('content')
    <div class="auth-shell">
        <div class="auth-copy">
            <p class="eyebrow" data-i18n="web.login.eyebrow">iOS &amp; Android marketplace</p>
            <h1 data-i18n="web.login.hero_title">Evdə lazım olan xidməti tez tap</h1>
            <p data-i18n="web.login.hero_body">
                Səs və ya mətnlə sorğu göndər — uyğun xidmətçilər çıxır, CONNECT ilə yazış.
                Eyni API, eyni qayda.
            </p>
            <ul class="auth-points">
                <li data-i18n="web.login.point1">Səsli sorğu → AI parse → match</li>
                <li data-i18n="web.login.point2">Profil, chat və təkliflər bir yerdə</li>
                <li data-i18n="web.login.point3">Balans paketlərlə — abunə yoxdur</li>
            </ul>
        </div>

        <section class="card card-hero">
            <h2 data-i18n="web.auth.login">Daxil ol</h2>
            <p class="muted" data-i18n="web.login.otp_hint">Telefonla OTP — yeni nömrə avtomatik qeydiyyat olunur, mövcud hesab isə daxil olur.</p>
            <div class="stack mt">
                <input id="phone" type="tel" placeholder="+994501111111" autocomplete="tel">
                <button type="button" id="send-otp" class="btn btn-primary" data-i18n="web.login.send_otp">Kod göndər</button>
                <input id="otp" type="text" placeholder="OTP" inputmode="numeric" autocomplete="one-time-code" data-i18n-placeholder="web.login.otp_placeholder">
                <button type="button" id="verify-otp" class="btn btn-secondary" data-i18n="web.login.continue">Davam et</button>
            </div>
            <p class="muted mt" data-i18n="web.login.demo_code">Demo kod: 123456</p>
        </section>
    </div>

    <section class="card card-log mt">
        <h2 data-i18n="web.debug.log">{{ wt('web.debug.log', 'Log') }}</h2>
        <pre id="log"></pre>
    </section>
@endsection
