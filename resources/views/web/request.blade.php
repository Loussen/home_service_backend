@extends('web.layout')

@section('title', 'MySancho Web · Sorğu yarat')
@section('page', 'request')

@section('content')
    <div class="grid-main">
        <section class="card">
            <h2>Hesab</h2>
            <div class="stack">
                <select id="role">
                    <option value="client">Ailə (client)</option>
                    <option value="provider">Xidmət göstərən (provider)</option>
                </select>
                <button type="button" id="set-role" class="btn btn-outline">Rolu seç</button>
                <button type="button" id="logout" class="btn btn-danger">Çıxış</button>
            </div>
        </section>

        <section class="card">
            <h2>Sorğu yarat (client)</h2>
            <div class="form-row form-row-2">
                <input id="text" class="span-2" type="text" placeholder="Məs: Nərimanovda sabah 2 saatlıq it gəzdirmə lazımdır">
                <div class="span-2 place-wrap">
                    <input id="place-search" type="text" placeholder="Ünvan axtar (Google Places)">
                    <div id="place-suggestions" class="suggestions"></div>
                </div>
                <input id="lat" type="text" placeholder="Latitude" value="40.4093">
                <input id="lng" type="text" placeholder="Longitude" value="49.8671">
                <button type="button" id="create-request" class="btn btn-primary">Sorğu yarat</button>
                <button type="button" id="refresh-request" class="btn btn-outline">Sorğunu yenilə</button>
            </div>
            <p id="request-info" class="muted mt">Sorğu yoxdur</p>
            <div id="map" class="map mt"></div>
            <p id="place-label" class="muted">Google Map. Ünvan axtar və ya “Mənim yerim”.</p>
        </section>
    </div>

    <section class="card mt">
        <div class="section-head">
            <h2>Match-lər</h2>
            <span id="match-count" class="pill">0 nəticə</span>
        </div>
        <div id="matches" class="matches"></div>
    </section>

    <section class="card mt">
        <h2>Log</h2>
        <pre id="log"></pre>
    </section>
@endsection
