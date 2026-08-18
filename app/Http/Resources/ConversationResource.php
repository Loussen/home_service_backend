<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Conversation */
class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $me = $request->user();
        $other = $me && $this->client_id === $me->id ? $this->provider : $this->client;

        return [
            'id' => $this->id,
            'client_id' => $this->client_id,
            'provider_id' => $this->provider_id,
            'provider_profile_id' => $this->provider_profile_id,
            'service_request_id' => $this->service_request_id,
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
            'last_message' => new MessageResource($this->whenLoaded('lastMessage')),
            'messages' => MessageResource::collection($this->whenLoaded('messages')),
        ];
    }
}
