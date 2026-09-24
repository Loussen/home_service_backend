<?php

namespace App\Http\Resources;

use App\Models\Offer;
use App\Support\PublicMediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Conversation */
class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $me = $request->user();
        $other = $me && $this->client_id === $me->id ? $this->provider : $this->client;

        $isBlocked = (bool) ($this->is_blocked ?? false);
        if (! $isBlocked && $me && $other) {
            $isBlocked = app(\App\Services\ModerationService::class)
                ->isBlockedEitherWay($me, $other);
        }
        $blockedByMe = (bool) ($this->blocked_by_me ?? false);
        if (! array_key_exists('blocked_by_me', $this->getAttributes()) && $me && $other) {
            $blockedByMe = app(\App\Services\ModerationService::class)
                ->blockedIdsFor($me)
                ->contains((int) $other->id);
        }
        $canMessage = array_key_exists('can_message', $this->getAttributes())
            ? (bool) $this->can_message
            : ! $isBlocked;

        $canSendOffer = false;
        if ($canMessage && $me && (int) $this->provider_id === (int) $me->id && $me->isProvider()) {
            $canSendOffer = ! $this->offers()
                ->whereIn('status', [Offer::PENDING, Offer::ACCEPTED, Offer::COMPLETED])
                ->exists();
        }

        return [
            'id' => $this->id,
            'client_id' => $this->client_id,
            'provider_id' => $this->provider_id,
            'provider_profile_id' => $this->provider_profile_id,
            'service_request_id' => $this->service_request_id,
            'service_request' => $this->when(
                $this->relationLoaded('serviceRequest') && $this->serviceRequest,
                function () use ($request) {
                    $sr = $this->serviceRequest;
                    $locale = \App\Support\RequestLocale::from($request);

                    return [
                        'id' => $sr->id,
                        'status' => $sr->status,
                        'transcribed_text' => $sr->transcribed_text,
                        'address' => $sr->address,
                        'audio_url' => $sr->audio_public_url,
                        'is_urgent' => (bool) $sr->is_urgent,
                        'parsed_criteria' => $sr->parsed_criteria,
                        'expires_at' => $sr->expires_at?->toIso8601String(),
                        'created_at' => $sr->created_at?->toIso8601String(),
                        'category' => $sr->relationLoaded('category') && $sr->category
                            ? [
                                'id' => $sr->category->id,
                                'name' => $sr->category->nameFor($locale),
                                'name_az' => $sr->category->name_az,
                            ]
                            : null,
                    ];
                },
            ),
            'is_blocked' => $isBlocked,
            'blocked_by_me' => $blockedByMe,
            'can_message' => $canMessage,
            'can_send_offer' => $canSendOffer,
            'last_message_at' => $this->last_message_at?->toIso8601String(),
            'unread_count' => (int) ($this->unread_count ?? 0),
            'other_user' => $other ? [
                'id' => $other->id,
                'name' => $other->name,
                'phone' => (! $isBlocked && $this->hasSharedContact()) ? $other->phone : null,
                'avatar_url' => PublicMediaUrl::make($other->avatar_url),
            ] : null,
            'provider_profile' => new ProviderProfileResource($this->whenLoaded('providerProfile')),
            'last_message' => $this->when(
                $this->relationLoaded('lastMessage') && $this->lastMessage,
                fn () => (new MessageResource($this->lastMessage))->resolve(),
            ),
            'messages' => $this->when(
                $this->relationLoaded('messages'),
                fn () => MessageResource::collection($this->messages)->resolve(),
            ),
        ];
    }
}
