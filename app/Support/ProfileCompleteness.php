<?php

namespace App\Support;

use App\Models\User;

class ProfileCompleteness
{
    /**
     * @return array{complete: bool, percent: int, missing: list<string>, profile_id: ?int}
     */
    public static function forUser(User $user): array
    {
        if (! $user->isProvider()) {
            return [
                'complete' => true,
                'percent' => 100,
                'missing' => [],
                'profile_id' => null,
            ];
        }

        $profile = $user->providerProfiles()
            ->with(['categories', 'schedules'])
            ->orderByDesc('id')
            ->first();

        if (! $profile) {
            return [
                'complete' => false,
                'percent' => 0,
                'missing' => ['profile'],
                'profile_id' => null,
            ];
        }

        $checks = [
            'categories' => $profile->categories->isNotEmpty()
                || filled($profile->category_id),
            'location' => filled($profile->city_id)
                || filled($profile->district_id)
                || filled($profile->city)
                || filled($profile->district),
            'schedule' => $profile->schedules
                ->where('is_available', true)
                ->isNotEmpty(),
            'about' => filled($profile->title) || filled($profile->bio),
        ];

        $missing = [];
        foreach ($checks as $key => $ok) {
            if (! $ok) {
                $missing[] = $key;
            }
        }

        $done = count($checks) - count($missing);

        return [
            'complete' => $missing === [],
            'percent' => (int) round(($done / count($checks)) * 100),
            'missing' => $missing,
            'profile_id' => $profile->id,
        ];
    }
}
