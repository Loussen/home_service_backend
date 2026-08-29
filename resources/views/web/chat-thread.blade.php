@extends('web.layout')

@section('title', 'MySancho · Söhbət')
@section('page', 'chat-thread')
@section('conversation_id', (string) $conversationId)

@section('content')
    <section class="card">
        <div class="section-head">
            <div>
                <a class="muted" href="{{ route('web.chat') }}">← Söhbətlər</a>
                <h2 id="thread-title">Söhbət #{{ $conversationId }}</h2>
            </div>
            <button type="button" id="open-offer" class="btn btn-primary btn-inline" hidden>
                Təklif göndər
            </button>
        </div>
        <div id="thread-messages" class="thread"></div>
        <div class="composer mt">
            <input id="chat-body" type="text" placeholder="Mesaj yaz…">
            <button type="button" id="send-message" class="btn btn-primary btn-inline">Göndər</button>
        </div>
    </section>

    <div id="offer-modal" class="modal" hidden>
        <div class="modal-card">
            <h3>Təklif göndər</h3>
            <p class="muted">Eyni API: <code>POST /conversations/{id}/offers</code></p>
            <label class="field">
                <span>Tarix və saat</span>
                <input id="offer-when" type="datetime-local">
            </label>
            <label class="field">
                <span>Qiymət (AZN)</span>
                <input id="offer-price" type="number" min="1" step="1" placeholder="məs. 25">
            </label>
            <label class="field">
                <span>Müddət (saat, istəyə bağlı)</span>
                <input id="offer-hours" type="number" min="0.5" max="24" step="0.5" placeholder="məs. 2">
            </label>
            <label class="field">
                <span>Qeyd</span>
                <input id="offer-note" type="text" maxlength="500" placeholder="İstəyə bağlı">
            </label>
            <div class="modal-actions">
                <button type="button" id="offer-cancel" class="btn btn-outline btn-inline">Bağla</button>
                <button type="button" id="offer-submit" class="btn btn-primary btn-inline">Göndər</button>
            </div>
        </div>
    </div>

    <div id="review-modal" class="modal" hidden>
        <div class="modal-card">
            <h3>Rəy yaz</h3>
            <p class="muted">Eyni API: <code>POST /offers/{id}/reviews</code></p>
            <input type="hidden" id="review-offer-id">
            <label class="field">
                <span>Ulduz (1–5)</span>
                <select id="review-rating">
                    <option value="5">5</option>
                    <option value="4">4</option>
                    <option value="3">3</option>
                    <option value="2">2</option>
                    <option value="1">1</option>
                </select>
            </label>
            <label class="field">
                <span>Şərh (istəyə bağlı)</span>
                <input id="review-comment" type="text" maxlength="1000" placeholder="Qısa rəy">
            </label>
            <div class="modal-actions">
                <button type="button" id="review-cancel" class="btn btn-outline btn-inline">Bağla</button>
                <button type="button" id="review-submit" class="btn btn-primary btn-inline">Göndər</button>
            </div>
        </div>
    </div>

    <section class="card card-log mt">
        <h2>Log</h2>
        <pre id="log"></pre>
    </section>
@endsection
