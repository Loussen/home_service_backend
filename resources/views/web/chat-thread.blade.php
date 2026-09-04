@extends('web.layout')

@section('title', 'My Sancho · '.wt('web.dashboard.cta_chats', 'Söhbət'))
@section('page', 'chat-thread')
@section('conversation_id', (string) $conversationId)

@section('content')
    <section class="card">
        <div class="section-head">
            <div>
                <a class="muted" href="{{ route('web.chat') }}" data-i18n="web.chat.back">{{ wt('web.chat.back', '← Söhbətlər') }}</a>
                <h2 id="thread-title">{{ str_replace('{id}', (string) $conversationId, wt('web.chat.thread_heading', 'Söhbət #{id}')) }}</h2>
            </div>
            <button type="button" id="open-offer" class="btn btn-primary btn-inline" hidden data-i18n="web.offer.send">
                {{ wt('web.offer.send', 'Təklif göndər') }}
            </button>
        </div>
        <div id="thread-messages" class="thread"></div>
        <div class="composer mt">
            <input id="chat-body" type="text" placeholder="{{ wt('web.chat.message_ph', 'Mesaj yaz…') }}" data-i18n-placeholder="web.chat.message_ph">
            <button type="button" id="send-message" class="btn btn-primary btn-inline" data-i18n="web.common.send">{{ wt('web.common.send', 'Göndər') }}</button>
        </div>
    </section>

    <div id="offer-modal" class="modal" hidden>
        <div class="modal-card">
            <h3 data-i18n="web.offer.send">{{ wt('web.offer.send', 'Təklif göndər') }}</h3>
            <label class="field">
                <span data-i18n="web.offer.when">{{ wt('web.offer.when', 'Tarix və saat') }}</span>
                <input id="offer-when" type="datetime-local">
            </label>
            <label class="field">
                <span data-i18n="web.offer.price">{{ wt('web.offer.price', 'Qiymət (AZN)') }}</span>
                <input id="offer-price" type="number" min="1" step="1" placeholder="{{ wt('web.offer.price_ph', 'məs. 25') }}" data-i18n-placeholder="web.offer.price_ph">
            </label>
            <label class="field">
                <span data-i18n="web.offer.hours">{{ wt('web.offer.hours', 'Müddət (saat, istəyə bağlı)') }}</span>
                <input id="offer-hours" type="number" min="0.5" max="24" step="0.5" placeholder="{{ wt('web.offer.hours_ph', 'məs. 2') }}" data-i18n-placeholder="web.offer.hours_ph">
            </label>
            <label class="field">
                <span data-i18n="web.offer.note">{{ wt('web.offer.note', 'Qeyd') }}</span>
                <input id="offer-note" type="text" maxlength="500" placeholder="{{ wt('web.offer.note_ph', 'İstəyə bağlı') }}" data-i18n-placeholder="web.offer.note_ph">
            </label>
            <div class="modal-actions">
                <button type="button" id="offer-cancel" class="btn btn-outline btn-inline" data-i18n="web.common.close">{{ wt('web.common.close', 'Bağla') }}</button>
                <button type="button" id="offer-submit" class="btn btn-primary btn-inline" data-i18n="web.common.send">{{ wt('web.common.send', 'Göndər') }}</button>
            </div>
        </div>
    </div>

    <div id="review-modal" class="modal" hidden>
        <div class="modal-card">
            <h3 data-i18n="web.review.write">{{ wt('web.review.write', 'Rəy yaz') }}</h3>
            <input type="hidden" id="review-offer-id">
            <label class="field">
                <span data-i18n="web.review.rating">{{ wt('web.review.rating', 'Ulduz (1–5)') }}</span>
                <select id="review-rating">
                    <option value="5">5</option>
                    <option value="4">4</option>
                    <option value="3">3</option>
                    <option value="2">2</option>
                    <option value="1">1</option>
                </select>
            </label>
            <label class="field">
                <span data-i18n="web.review.comment">{{ wt('web.review.comment', 'Şərh (istəyə bağlı)') }}</span>
                <input id="review-comment" type="text" maxlength="1000" placeholder="{{ wt('web.review.comment_ph', 'Qısa rəy') }}" data-i18n-placeholder="web.review.comment_ph">
            </label>
            <div class="modal-actions">
                <button type="button" id="review-cancel" class="btn btn-outline btn-inline" data-i18n="web.common.close">{{ wt('web.common.close', 'Bağla') }}</button>
                <button type="button" id="review-submit" class="btn btn-primary btn-inline" data-i18n="web.common.send">{{ wt('web.common.send', 'Göndər') }}</button>
            </div>
        </div>
    </div>

    <section class="card card-log mt">
        <h2 data-i18n="web.debug.log">{{ wt('web.debug.log', 'Log') }}</h2>
        <pre id="log"></pre>
    </section>
@endsection
