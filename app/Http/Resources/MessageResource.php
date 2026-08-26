<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Message */
class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'conversation_id' => $this->conversation_id,
            'sender_id' => $this->sender_id,
            'is_mine' => $request->user() !== null
                && (int) $this->sender_id === (int) $request->user()->id,
            'type' => $this->type ?? 'text',
            'body' => $this->body,
            'attachment_url' => $this->attachment_url,
            'offer' => $this->when(
                $this->relationLoaded('offer') && $this->offer,
                fn () => (new OfferResource($this->offer))->resolve(),
            ),
            'read_at' => $this->read_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
