<?php

namespace App\Support;

use Carbon\Carbon;

class RequestFilters
{
    /**
     * @return array{category_id: ?int, parsed_criteria: array<string, mixed>}
     */
    public static function initialCriteria(
        ?int $categoryId = null,
        ?string $scheduledAt = null,
        ?string $timeSlot = null,
        ?int $childAge = null,
        ?bool $hasPet = null,
        ?float $budgetMax = null,
        ?string $rawText = null,
    ): array {
        $criteria = array_filter([
            'raw_text' => $rawText,
            'user_time_slot' => $timeSlot,
            'user_scheduled_at' => $scheduledAt,
            'user_child_age' => $childAge,
            'user_budget_max' => $budgetMax,
        ], fn ($v) => $v !== null && $v !== '');

        if ($hasPet !== null) {
            $criteria['user_has_pet'] = $hasPet;
        }

        return [
            'category_id' => $categoryId,
            'parsed_criteria' => $criteria,
        ];
    }

    /**
     * @param  array<string, mixed>  $parsed
     * @param  array<string, mixed>  $existingCriteria
     * @return array<string, mixed>
     */
    public static function mergeUserFilters(
        array $parsed,
        array $existingCriteria,
        ?int $categoryId,
        callable $slotFromClock,
    ): array {
        if (! empty($existingCriteria['user_scheduled_at'])) {
            $dt = Carbon::parse($existingCriteria['user_scheduled_at']);
            $parsed['scheduled_at'] = $dt->toIso8601String();
            $parsed['time_hhmm'] = $dt->format('H:i');
            $parsed['time_slot'] = $slotFromClock($parsed['time_hhmm']);
        } elseif (! empty($existingCriteria['user_time_slot'])) {
            $parsed['time_slot'] = $existingCriteria['user_time_slot'];
        }

        if ($categoryId) {
            $parsed['user_category_id'] = $categoryId;
        }
        if (array_key_exists('user_child_age', $existingCriteria)) {
            $parsed['child_age'] = $existingCriteria['user_child_age'];
        }
        if (array_key_exists('user_has_pet', $existingCriteria)) {
            $parsed['has_pet'] = $existingCriteria['user_has_pet'];
        }
        if (array_key_exists('user_budget_max', $existingCriteria)) {
            $parsed['budget_max'] = $existingCriteria['user_budget_max'];
        }

        return $parsed;
    }
}
