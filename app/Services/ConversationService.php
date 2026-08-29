<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Offer;
use App\Models\ProviderProfile;
use App\Models\RequestMatch;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Support\ConnectQuota;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ConversationService
{
    public function __construct(
        private readonly ModerationService $moderation,
        private readonly BookingService $bookings,
        private readonly WalletService $wallet,
    ) {}

    public function listFor(User $user): LengthAwarePaginator
    {
        $blockedIds = $this->moderation->hiddenUserIdsFor($user);

        return Conversation::query()
            ->where(function ($q) use ($user) {
                $q->where('client_id', $user->id)
                    ->orWhere('provider_id', $user->id);
            })
            ->when($blockedIds->isNotEmpty(), function ($q) use ($user, $blockedIds) {
                $q->where(function ($inner) use ($user, $blockedIds) {
                    $inner->where(function ($clientSide) use ($user, $blockedIds) {
                        $clientSide->where('client_id', $user->id)
                            ->whereNotIn('provider_id', $blockedIds);
                    })->orWhere(function ($providerSide) use ($user, $blockedIds) {
                        $providerSide->where('provider_id', $user->id)
                            ->whereNotIn('client_id', $blockedIds);
                    });
                });
            })
            ->with([
                'client:id,name,phone,avatar_url',
                'provider:id,name,phone,avatar_url',
                'providerProfile.category',
                'providerProfile.categories',
                'lastMessage.offer',
            ])
            ->withCount([
                'messages as unread_count' => function ($q) use ($user) {
                    $q->whereNull('read_at')->where('sender_id', '!=', $user->id);
                },
            ])
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->paginate(40);
    }

    public function getFor(User $user, int $id): Conversation
    {
        $conversation = Conversation::query()
            ->where(function ($q) use ($user) {
                $q->where('client_id', $user->id)
                    ->orWhere('provider_id', $user->id);
            })
            ->with([
                'client:id,name,phone,avatar_url',
                'provider:id,name,phone,avatar_url',
                'providerProfile.category',
                'providerProfile.categories',
                'messages' => fn ($q) => $q->with(['offer.reviews.reviewer:id,name'])->orderBy('created_at')->orderBy('id'),
            ])
            ->find($id);

        abort_if(! $conversation, 404, 'Conversation not found');

        $otherId = $conversation->client_id === $user->id
            ? $conversation->provider_id
            : $conversation->client_id;
        $other = User::query()->find($otherId);
        if ($other) {
            $this->moderation->assertNotBlocked($user, $other);
        }

        $this->markRead($conversation, $user);

        $conversation->unsetRelation('messages');
        $conversation->load([
            'messages' => fn ($q) => $q->with(['offer.reviews.reviewer:id,name'])->orderBy('created_at')->orderBy('id'),
        ]);

        return $conversation;
    }

    public function open(
        User $client,
        int $providerProfileId,
        ?int $serviceRequestId = null,
        ?string $message = null,
    ): Conversation {
        abort_unless($client->isClient(), 403, 'Connect yalnız müştəri üçündür');
        abort_if($client->status === 'blocked', 403, 'Account blocked');

        $profile = ProviderProfile::query()
            ->with('user')
            ->find($providerProfileId);

        abort_if(! $profile || ! $profile->is_active, 404, 'Provider not found');
        abort_if($profile->user_id === $client->id, 422, 'Cannot connect to your own profile');
        $this->moderation->assertNotBlocked($client, $profile->user);

        $serviceRequest = null;
        if ($serviceRequestId) {
            $serviceRequest = ServiceRequest::query()
                ->where('user_id', $client->id)
                ->find($serviceRequestId);
            abort_if(! $serviceRequest, 404, 'Request not found');
        }

        return DB::transaction(function () use ($client, $profile, $serviceRequest, $message) {
            $client = User::query()->lockForUpdate()->find($client->id) ?? $client;

            $conversation = Conversation::query()
                ->where('client_id', $client->id)
                ->where('provider_id', $profile->user_id)
                ->where('provider_profile_id', $profile->id)
                ->first();

            $isNew = false;
            if (! $conversation) {
                $quota = ConnectQuota::snapshot($client);
                ConnectQuota::assertCanOpen($quota);
                if ((float) $quota['fee'] > 0) {
                    $this->wallet->debit($client, (float) $quota['fee'], 'connect_fee', 'wallet', [
                        'provider_profile_id' => $profile->id,
                    ]);
                    $client = $client->fresh() ?? $client;
                }
                $conversation = Conversation::query()->create([
                    'client_id' => $client->id,
                    'provider_id' => $profile->user_id,
                    'provider_profile_id' => $profile->id,
                    'service_request_id' => $serviceRequest?->id,
                ]);
                $isNew = true;
            }

            if ($serviceRequest && ! $conversation->service_request_id) {
                $conversation->update(['service_request_id' => $serviceRequest->id]);
            }

            $body = trim((string) $message);
            if ($body === '' && $isNew) {
                $snippet = $serviceRequest?->transcribed_text;
                $body = $snippet
                    ? "Salam! Bu sorğu üzrə sizinlə işləmək istəyirəm:\n{$snippet}"
                    : 'Salam! Sizinlə işləmək istəyirəm.';
            }

            if ($body !== '') {
                $this->postMessage($conversation, $client, $body);
            }

            return $this->getFor($client, $conversation->id);
        });
    }

    public function replyAsProvider(
        User $provider,
        int $serviceRequestId,
        ?int $providerProfileId = null,
        ?string $message = null,
    ): Conversation {
        abort_unless($provider->isProvider(), 403, 'Cavab yalnız xidmət göstərən üçündür');

        $matchQuery = RequestMatch::query()
            ->where('service_request_id', $serviceRequestId)
            ->whereHas('providerProfile', fn ($q) => $q->where('user_id', $provider->id));

        if ($providerProfileId) {
            $matchQuery->where('provider_profile_id', $providerProfileId);
        }

        $match = $matchQuery->with(['providerProfile', 'serviceRequest.user'])->first();
        abort_if(! $match, 404, 'Bu iş sizin üçün tapılmadı');

        $request = $match->serviceRequest;
        abort_if(! $request, 404, 'Sorğu tapılmadı');

        $profile = $match->providerProfile;
        $client = $request->user;
        abort_if(! $client, 404, 'Müştəri tapılmadı');
        $this->moderation->assertNotBlocked($provider, $client);

        return DB::transaction(function () use ($provider, $client, $profile, $request, $message) {
            $conversation = Conversation::query()->firstOrCreate(
                [
                    'client_id' => $client->id,
                    'provider_id' => $provider->id,
                    'provider_profile_id' => $profile->id,
                ],
                [
                    'service_request_id' => $request->id,
                ]
            );

            $body = trim((string) $message);
            if ($body === '' && $conversation->wasRecentlyCreated) {
                $body = 'Salam! Sorğunuzu gördüm, kömək edə bilərəm.';
            }
            if ($body !== '') {
                $this->postMessage($conversation, $provider, $body);
            }

            return $this->getFor($provider, $conversation->id);
        });
    }

    public function send(User $user, int $conversationId, string $body): Message
    {
        $conversation = Conversation::query()
            ->where(function ($q) use ($user) {
                $q->where('client_id', $user->id)
                    ->orWhere('provider_id', $user->id);
            })
            ->find($conversationId);

        abort_if(! $conversation, 404, 'Conversation not found');

        $otherId = $conversation->client_id === $user->id
            ? $conversation->provider_id
            : $conversation->client_id;
        $other = User::query()->find($otherId);
        if ($other) {
            $this->moderation->assertNotBlocked($user, $other);
        }

        return $this->postMessage($conversation, $user, $body);
    }

    public function createOffer(User $user, int $conversationId, array $data): Conversation
    {
        abort_unless($user->isProvider(), 403, 'Təklifi yalnız icraçı göndərir');

        $conversation = $this->ownedConversation($user, $conversationId);
        abort_if($conversation->provider_id !== $user->id, 403, 'Təklifi yalnız icraçı göndərir');

        $blocking = $conversation->offers()
            ->whereIn('status', [Offer::PENDING, Offer::ACCEPTED, Offer::COMPLETED])
            ->exists();
        abort_if($blocking, 422, 'Bu söhbətdə artıq aktiv və ya tamamlanmış təklif var');

        return DB::transaction(function () use ($conversation, $user, $data) {
            $offer = Offer::query()->create([
                'conversation_id' => $conversation->id,
                'proposed_by' => $user->id,
                'scheduled_at' => $data['scheduled_at'],
                'duration_hours' => $data['duration_hours'] ?? null,
                'price_azn' => $data['price_azn'],
                'note' => $data['note'] ?? null,
                'status' => Offer::PENDING,
            ]);

            $when = $offer->scheduled_at?->timezone(config('app.timezone'))->format('d.m.Y H:i');
            $price = number_format((float) $offer->price_azn, 0, '.', '');
            $this->postMessage(
                $conversation,
                $user,
                "Təklif: {$when} · {$price} AZN",
                type: 'offer',
                offerId: $offer->id,
            );

            return $this->getFor($user, $conversation->id);
        });
    }

    public function respondOffer(User $user, int $offerId, bool $accept): Conversation
    {
        $offer = $this->offerFor($user, $offerId);
        abort_unless($offer->isPending(), 422, 'Təklif artıq cavablanıb');
        abort_if($offer->conversation->client_id !== $user->id, 403, 'Təklifi müştəri qəbul edir');

        $offer->update([
            'status' => $accept ? Offer::ACCEPTED : Offer::DECLINED,
            'accepted_at' => $accept ? now() : null,
        ]);

        if ($accept) {
            $this->bookings->createFromAcceptedOffer($offer->fresh());
        }

        $this->postMessage(
            $offer->conversation,
            $user,
            $accept ? 'Təklif qəbul edildi.' : 'Təklif rədd edildi.',
        );

        return $this->getFor($user, $offer->conversation_id);
    }

    public function completeOffer(User $user, int $offerId): Conversation
    {
        $offer = $this->offerFor($user, $offerId);
        abort_unless($offer->status === Offer::ACCEPTED, 422, 'Yalnız təsdiqlənmiş təklifi tamamlamaq olar');

        $offer->update([
            'status' => Offer::COMPLETED,
            'completed_at' => now(),
        ]);

        $this->bookings->markCompleted($offer);

        $this->postMessage($offer->conversation, $user, 'İş tamamlandı.');

        return $this->getFor($user, $offer->conversation_id);
    }

    public function cancelOffer(User $user, int $offerId): Conversation
    {
        $offer = $this->offerFor($user, $offerId);
        abort_unless($offer->isPending(), 422, 'Yalnız gözləyən təklifi ləğv etmək olar');
        abort_if($offer->proposed_by !== $user->id, 403, 'Təklifi göndərən ləğv edir');

        $offer->update(['status' => Offer::CANCELLED]);
        $this->postMessage($offer->conversation, $user, 'Təklif ləğv edildi.');

        return $this->getFor($user, $offer->conversation_id);
    }

    private function ownedConversation(User $user, int $id): Conversation
    {
        $conversation = Conversation::query()
            ->where(function ($q) use ($user) {
                $q->where('client_id', $user->id)
                    ->orWhere('provider_id', $user->id);
            })
            ->find($id);

        abort_if(! $conversation, 404, 'Conversation not found');

        $otherId = $conversation->client_id === $user->id
            ? $conversation->provider_id
            : $conversation->client_id;
        $other = User::query()->find($otherId);
        if ($other) {
            $this->moderation->assertNotBlocked($user, $other);
        }

        return $conversation;
    }

    private function offerFor(User $user, int $offerId): Offer
    {
        $offer = Offer::query()->with('conversation')->find($offerId);
        abort_if(! $offer, 404, 'Təklif tapılmadı');

        $c = $offer->conversation;
        abort_if(
            $c->client_id !== $user->id && $c->provider_id !== $user->id,
            404,
            'Təklif tapılmadı'
        );

        return $offer;
    }

    private function postMessage(
        Conversation $conversation,
        User $sender,
        string $body,
        string $type = 'text',
        ?int $offerId = null,
    ): Message {
        $message = Message::query()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $sender->id,
            'type' => $type,
            'body' => $body,
            'offer_id' => $offerId,
        ]);

        $conversation->update(['last_message_at' => now()]);

        return $message;
    }

    private function markRead(Conversation $conversation, User $user): void
    {
        $conversation->messages()
            ->whereNull('read_at')
            ->where('sender_id', '!=', $user->id)
            ->update(['read_at' => now()]);
    }
}
