<?php

namespace App\Http\Resources;

use App\Models\Offer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Conversation */
class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $me = $request->user();
        $other = $me && $this->client_id === $me->id ? $this->provider : $this->client;

        $canSendOffer = false;
        if ($me && (int) $this->provider_id === (int) $me->id && $me->isProvider()) {
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
            'can_send_offer' => $canSendOffer,
            'last_message_at' => $this->last_message_at?->toIso8601String(),
            'unread_count' => $this->when(
                isset($this->unread_count),
                (int) $this->unread_count
            ),
            'other_user' => $other ? [
                'id' => $other->id,
                'name' => $other->name,
                'phone' => $this->hasSharedContact() ? $other->phone : null,
                'avatar_url' => $other->avatar_url,
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
