@extends('web.layout')

@section('title', 'MySancho Web · Login')
@section('page', 'login')

@section('content')
    <section class="card card-narrow card-hero">
        <h2>Giriş (OTP)</h2>
        <p class="muted">Əvvəl daxil olun, sonra sorğu yarat səhifəsinə keçin.</p>
        <div class="stack mt">
            <input id="phone" type="tel" placeholder="+994501111111" autocomplete="tel">
            <button type="button" id="send-otp" class="btn btn-primary">Kod göndər</button>
            <input id="otp" type="text" placeholder="OTP kodu" inputmode="numeric" autocomplete="one-time-code">
            <button type="button" id="verify-otp" class="btn btn-secondary">Daxil ol</button>
        </div>
        <p class="muted mt">Demo kod: <b>123456</b></p>
        <p class="muted">Daxil olduqdan sonra avtomatik sorğu səhifəsinə yönləndiriləcəksiniz.</p>
    </section>

    <section class="card mt">
        <h2>Log</h2>
        <pre id="log"></pre>
    </section>
@endsection
