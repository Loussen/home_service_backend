@extends('web.layout')

@section('title', 'MySancho Web · Kateqoriyalar')
@section('page', 'categories')

@section('content')
    <section class="card">
        <h2>Kateqoriya seçimi</h2>
        <p class="muted">Maksimum 3 kateqoriya seç. Provider profil yaradanda istifadə olunacaq.</p>
        <div id="category-list" class="chips mt"></div>
        <p class="muted mt">Seçilənlər: <span id="selected-count">0</span>/3</p>
        <div class="actions mt">
            <button type="button" id="save-categories" class="btn btn-primary">Seçimi yadda saxla</button>
        </div>
    </section>

    <section class="card mt">
        <h2>Log</h2>
        <pre id="log"></pre>
    </section>
@endsection
