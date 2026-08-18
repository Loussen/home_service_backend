<?php

namespace App\Support;

use App\Models\RequestMatch;
use App\Models\ServiceRequest;

final class MatchReasons
{
    /**
     * @return list<array{key: string, params: array<string, string>}>
     */
    public static function for(RequestMatch $match, ?ServiceRequest $request = null): array
    {
        $request ??= $match->serviceRequest;
        $reasons = [];

        $km = number_format((float) $match->distance_km, 1, '.', '');
        $reasons[] = [
            'key' => 'match.reason.distance',
            'params' => ['km' => $km],
        ];

        $breakdown = $match->score_breakdown ?? [];
        $schedule = (int) ($breakdown['schedule'] ?? 50);
        if ($schedule >= 80) {
            $reasons[] = ['key' => 'match.reason.schedule_ok', 'params' => []];
        } elseif ($schedule > 0 && $schedule < 50) {
            $reasons[] = ['key' => 'match.reason.schedule_miss', 'params' => []];
        }

        $categoryName = $request?->category?->name_az;
        if (filled($categoryName)) {
            $reasons[] = [
                'key' => 'match.reason.category',
                'params' => ['name' => (string) $categoryName],
            ];
        }

        return $reasons;
    }
}
