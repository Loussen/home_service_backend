<?php

namespace App\Support;

use App\Models\ServiceRequest;
use Carbon\CarbonInterface;

class RequestTtl
{
    /** @return list<int> */
    public static function optionsHours(): array
    {
        $raw = config('homeservice.request_ttl_options_hours', [1, 3, 6]);
        if (! is_array($raw)) {
            $raw = [1, 3, 6];
        }
        $hours = array_values(array_unique(array_filter(array_map(
            static fn ($v) => is_numeric($v) ? (int) $v : null,
            $raw,
        ), static fn ($v) => $v !== null && $v > 0)));

        return $hours !== [] ? $hours : [1, 3, 6];
    }

    public static function defaultHours(): int
    {
        $default = (int) config('homeservice.request_ttl_default_hours', 1);
        $options = self::optionsHours();
        if (in_array($default, $options, true)) {
            return $default;
        }

        return $options[0];
    }

    public static function resolveHours(?int $requested): int
    {
        $options = self::optionsHours();
        if ($requested !== null && in_array($requested, $options, true)) {
            return $requested;
        }

        return self::defaultHours();
    }

    public static function expiresAtFromNow(?int $requestedHours = null): CarbonInterface
    {
        return now()->addHours(self::resolveHours($requestedHours));
    }

    /**
     * Mark past-due requests as expired. Returns true if this request is (now) expired.
     */
    public static function expireIfNeeded(ServiceRequest $request): bool
    {
        if ($request->status === 'expired') {
            return true;
        }
        if (in_array($request->status, ['completed', 'cancelled'], true)) {
            return false;
        }
        if ($request->expires_at && $request->expires_at->isPast()) {
            $request->forceFill(['status' => 'expired'])->save();

            return true;
        }

        return false;
    }

    public static function expireOverdue(?int $userId = null): int
    {
        $query = ServiceRequest::query()
            ->whereNotIn('status', ['expired', 'completed', 'cancelled'])
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now());

        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        return $query->update(['status' => 'expired']);
    }

    public static function isOpenForContact(ServiceRequest $request): bool
    {
        if (self::expireIfNeeded($request)) {
            return false;
        }

        return in_array($request->status, ['processing', 'active', 'matched'], true);
    }
}
