@extends('web.layout')

@section('title', 'My Sancho · Kateqoriyalar')
@section('page', 'categories')

@section('content')
    <header class="page-hero">
        <p class="eyebrow">İcraçı</p>
        <h1>Kateqoriyalar</h1>
        <p>Maksimum 3 yarpaq kateqoriya seç — profil yaradanda istifadə olunur.</p>
    </header>

    <section class="card">
        <div id="category-list" class="chips"></div>
        <p class="muted mt">Seçilənlər: <span id="selected-count">0</span>/3</p>
        <div class="actions mt">
            <button type="button" id="save-categories" class="btn btn-primary">Seçimi yadda saxla</button>
        </div>
    </section>

    <section class="card card-log mt">
        <h2>Log</h2>
        <pre id="log"></pre>
    </section>
@endsection
