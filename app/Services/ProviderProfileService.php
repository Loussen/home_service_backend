<?php

namespace App\Services;

use App\Models\ProviderProfile;
use App\Models\User;
use App\Repositories\ProviderProfileRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ProviderProfileService
{
    public function __construct(
        private readonly ProviderProfileRepository $profiles,
    ) {}

    public function list(User $user)
    {
        return $this->profiles->listForUser($user->id);
    }

    public function create(User $user, array $data): ProviderProfile
    {
        $profile = $this->profiles->create([
            'user_id' => $user->id,
            'category_id' => $data['category_id'],
            'title' => $data['title'] ?? null,
            'bio' => $data['bio'] ?? null,
            'audio_intro_url' => $data['audio_intro_url'] ?? null,
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'city' => $data['city'] ?? null,
            'district' => $data['district'] ?? null,
        ]);

        if (! empty($data['schedules'])) {
            $this->profiles->syncSchedules($profile, $data['schedules']);
        }

        return $profile->fresh(['category', 'schedules']);
    }

    public function update(User $user, int $profileId, array $data): ProviderProfile
    {
        $profile = $this->profiles->findForUser($user->id, $profileId);

        abort_if(! $profile, 404, 'Profile not found');

        $profile = $this->profiles->update($profile, array_filter([
            'category_id' => $data['category_id'] ?? null,
            'title' => $data['title'] ?? null,
            'bio' => $data['bio'] ?? null,
            'audio_intro_url' => $data['audio_intro_url'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'city' => $data['city'] ?? null,
            'district' => $data['district'] ?? null,
            'is_active' => $data['is_active'] ?? null,
        ], fn ($v) => $v !== null));

        if (isset($data['schedules'])) {
            $this->profiles->syncSchedules($profile, $data['schedules']);
        }

        return $profile->fresh(['category', 'schedules']);
    }

    public function get(User $user, int $profileId): ProviderProfile
    {
        $profile = $this->profiles->findForUser($user->id, $profileId);
        abort_if(! $profile, 404, 'Profile not found');

        return $profile;
    }

    public function uploadAudioIntro(User $user, int $profileId, UploadedFile $audio): ProviderProfile
    {
        $profile = $this->get($user, $profileId);

        if ($profile->audio_intro_url && Storage::disk('public')->exists($profile->audio_intro_url)) {
            Storage::disk('public')->delete($profile->audio_intro_url);
        }

        $path = $audio->store("audio/intros/{$user->id}", 'public');

        return $this->profiles->update($profile, [
            'audio_intro_url' => $path,
        ])->load(['category', 'schedules']);
    }

    public function destroy(User $user, int $profileId): void
    {
        $profile = $this->get($user, $profileId);

        if ($profile->audio_intro_url && Storage::disk('public')->exists($profile->audio_intro_url)) {
            Storage::disk('public')->delete($profile->audio_intro_url);
        }

        $profile->delete();
    }
}
