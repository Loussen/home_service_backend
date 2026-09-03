@extends('web.layout')

@section('title', 'My Sancho · Daxil ol')
@section('page', 'login')

@section('content')
    <div class="auth-shell">
        <div class="auth-copy">
            <p class="eyebrow">iOS &amp; Android marketplace</p>
            <h1>Evdə lazım olan xidməti&nbsp;tez tap</h1>
            <p>
                Səs və ya mətnlə sorğu göndər — uyğun xidmətçilər çıxır, CONNECT ilə yazış.
                Eyni API, eyni qayda.
            </p>
            <ul class="auth-points">
                <li>Səsli sorğu → AI parse → match</li>
                <li>Profil, chat və təkliflər bir yerdə</li>
                <li>Balans paketlərlə — abunə yoxdur</li>
            </ul>
        </div>

        <section class="card card-hero">
            <h2>Daxil ol</h2>
            <p class="muted">Telefonla OTP — yeni nömrə avtomatik qeydiyyat olunur, mövcud hesab isə daxil olur.</p>
            <div class="stack mt">
                <input id="phone" type="tel" placeholder="+994501111111" autocomplete="tel">
                <button type="button" id="send-otp" class="btn btn-primary">Kod göndər</button>
                <input id="otp" type="text" placeholder="OTP kodu" inputmode="numeric" autocomplete="one-time-code">
                <button type="button" id="verify-otp" class="btn btn-secondary">Davam et</button>
            </div>
            <p class="muted mt">Demo kod: <b>123456</b></p>
        </section>
    </div>

    <section class="card card-log mt">
        <h2>Log</h2>
        <pre id="log"></pre>
    </section>
@endsection
