<?php

namespace App\Services;

use App\Models\Category;
use App\Models\City;
use App\Models\District;
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
        abort_unless($user->isProvider(), 403, 'Bu əməliyyat yalnız xidmət göstərən üçündür');

        $categoryIds = $this->normalizeCategoryIds($data);
        $location = $this->normalizeLocation($data);

        $profile = $this->profiles->create([
            'user_id' => $user->id,
            'category_id' => $categoryIds[0],
            'title' => $data['title'] ?? null,
            'bio' => $data['bio'] ?? null,
            'audio_intro_url' => $data['audio_intro_url'] ?? null,
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'city_id' => $location['city_id'],
            'district_id' => $location['district_id'],
            'city' => $location['city'],
            'district' => $location['district'],
            'is_active' => $data['is_active'] ?? true,
            'full_until' => ! empty($data['full_this_week']) ? now()->endOfWeek() : null,
            'quiet_hours_start' => $this->normalizeQuietTime($data['quiet_hours_start'] ?? null),
            'quiet_hours_end' => $this->normalizeQuietTime($data['quiet_hours_end'] ?? null),
        ]);

        $profile->syncCategoryIds($categoryIds);

        if (! empty($data['schedules'])) {
            $this->profiles->syncSchedules($profile, $data['schedules']);
        }

        return $profile->fresh(['category', 'categories', 'schedules']);
    }

    public function update(User $user, int $profileId, array $data): ProviderProfile
    {
        $profile = $this->profiles->findForUser($user->id, $profileId);

        abort_if(! $profile, 404, 'Profile not found');

        $payload = array_filter([
            'title' => $data['title'] ?? null,
            'bio' => $data['bio'] ?? null,
            'audio_intro_url' => $data['audio_intro_url'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'city' => $data['city'] ?? null,
            'district' => $data['district'] ?? null,
            'is_active' => $data['is_active'] ?? null,
        ], fn ($v) => $v !== null);

        if (isset($data['city_id']) || isset($data['district_id'])) {
            $location = $this->normalizeLocation($data);
            $payload['city_id'] = $location['city_id'];
            $payload['district_id'] = $location['district_id'];
            $payload['city'] = $location['city'];
            $payload['district'] = $location['district'];
        }

        if (isset($data['category_ids']) || isset($data['category_id'])) {
            $ids = $this->normalizeCategoryIds($data);
            $payload['category_id'] = $ids[0];
            $profile = $this->profiles->update($profile, $payload);
            $profile->syncCategoryIds($ids);
        } elseif ($payload !== []) {
            $profile = $this->profiles->update($profile, $payload);
        }

        $availability = $this->availabilityPayload($data);
        if ($availability !== []) {
            $profile = $this->profiles->update($profile, $availability);
        }

        if (isset($data['schedules'])) {
            $this->profiles->syncSchedules($profile, $data['schedules']);
        }

        return $profile->fresh(['category', 'categories', 'schedules']);
    }

    public function get(User $user, int $profileId): ProviderProfile
    {
        $profile = $this->profiles->findForUser($user->id, $profileId);
        abort_if(! $profile, 404, 'Profile not found');

        return $profile;
    }

    public function getPublic(int $profileId): ProviderProfile
    {
        $profile = $this->profiles->findPublic($profileId);
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
        ])->load(['category', 'categories', 'schedules']);
    }

    public function destroy(User $user, int $profileId): void
    {
        $profile = $this->get($user, $profileId);

        if ($profile->audio_intro_url && Storage::disk('public')->exists($profile->audio_intro_url)) {
            Storage::disk('public')->delete($profile->audio_intro_url);
        }

        $profile->delete();
    }

    /**
     * @return list<int>
     */
    private function normalizeCategoryIds(array $data): array
    {
        $ids = $data['category_ids'] ?? [];
        if ($ids === [] && ! empty($data['category_id'])) {
            $ids = [$data['category_id']];
        }
        $ids = array_values(array_unique(array_map('intval', $ids)));
        abort_if($ids === [], 422, 'Ən azı bir kateqoriya seçin');
        abort_if(count($ids) > 3, 422, 'Maksimum 3 kateqoriya seçmək olar');
        Category::assertAllLeaves($ids);

        return $ids;
    }

    /**
     * @return array{city_id: int|null, district_id: int|null, city: string|null, district: string|null}
     */
    private function normalizeLocation(array $data): array
    {
        $city = isset($data['city_id']) ? City::query()->find((int) $data['city_id']) : null;
        $district = isset($data['district_id']) ? District::query()->find((int) $data['district_id']) : null;

        if ($district && $city && (int) $district->city_id !== (int) $city->id) {
            abort(422, 'Rayon seçilmiş şəhərə aid deyil');
        }
        if ($district && ! $city) {
            $city = $district->city;
        }

        return [
            'city_id' => $city?->id,
            'district_id' => $district?->id,
            'city' => $city?->name ?? ($data['city'] ?? null),
            'district' => $district?->name ?? ($data['district'] ?? null),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function availabilityPayload(array $data): array
    {
        $payload = [];
        if (array_key_exists('full_this_week', $data)) {
            $payload['full_until'] = filter_var($data['full_this_week'], FILTER_VALIDATE_BOOLEAN)
                ? now()->endOfWeek()
                : null;
        }
        if (array_key_exists('quiet_hours_start', $data)) {
            $payload['quiet_hours_start'] = $this->normalizeQuietTime($data['quiet_hours_start']);
        }
        if (array_key_exists('quiet_hours_end', $data)) {
            $payload['quiet_hours_end'] = $this->normalizeQuietTime($data['quiet_hours_end']);
        }

        return $payload;
    }

    private function normalizeQuietTime(mixed $value): ?string
    {
        if ($value === null || $value === '' || $value === false) {
            return null;
        }
        $raw = substr((string) $value, 0, 5);
        if (! preg_match('/^\d{2}:\d{2}$/', $raw)) {
            return null;
        }

        return $raw;
    }
}
