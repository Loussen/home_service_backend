<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin \App\Models\VerificationDocument */
class VerificationDocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'provider_profile_id' => $this->provider_profile_id,
            'document_type' => $this->document_type,
            'file_url' => $this->file_url,
            'file_public_url' => $this->file_url
                ? Storage::disk('public')->url($this->file_url)
                : null,
            'status' => $this->status,
            'admin_note' => $this->admin_note,
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
