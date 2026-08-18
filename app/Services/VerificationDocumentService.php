<?php

namespace App\Services;

use App\Models\ProviderProfile;
use App\Models\User;
use App\Models\VerificationDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;

class VerificationDocumentService
{
    public function listFor(User $user): Collection
    {
        return VerificationDocument::query()
            ->where('user_id', $user->id)
            ->latest()
            ->limit(20)
            ->get();
    }

    public function upload(
        User $user,
        UploadedFile $file,
        string $documentType = 'id_card',
        ?int $providerProfileId = null,
    ): VerificationDocument {
        if ($providerProfileId) {
            $profile = ProviderProfile::query()
                ->where('user_id', $user->id)
                ->whereKey($providerProfileId)
                ->first();
            abort_if(! $profile, 422, 'Profil tapılmadı');
        }

        $pending = VerificationDocument::query()
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->exists();
        abort_if($pending, 422, 'Gözləyən sənəd var — admin cavabını gözləyin');

        $path = $file->store("verification/{$user->id}", 'public');

        return VerificationDocument::query()->create([
            'user_id' => $user->id,
            'provider_profile_id' => $providerProfileId,
            'document_type' => $documentType,
            'file_url' => $path,
            'status' => 'pending',
        ]);
    }
}
