<?php

namespace App\Support;

/**
 * Configurable AZ-style phone: {prefix}{operator}{subscriber}.
 * Example: +994 + 50 + XXXXXXX
 */
final class PhoneNumber
{
    public static function prefix(): string
    {
        $raw = trim((string) config('homeservice.phone_prefix', '+994'));
        $raw = preg_replace('/\s+/', '', $raw) ?: '+994';
        if (! str_starts_with($raw, '+')) {
            $raw = '+'.$raw;
        }

        return $raw;
    }

    /**
     * @return list<string>
     */
    public static function operatorCodes(): array
    {
        $raw = config('homeservice.phone_operator_codes', ['50', '55', '70', '77', '99', '10', '51']);
        if (is_string($raw)) {
            $raw = preg_split('/[\s,;]+/', $raw) ?: [];
        }
        if (! is_array($raw)) {
            return ['50', '55', '70', '77', '99', '10', '51'];
        }

        $codes = [];
        foreach ($raw as $code) {
            $digits = preg_replace('/\D+/', '', (string) $code) ?: '';
            if ($digits !== '') {
                $codes[$digits] = $digits;
            }
        }
        $list = array_values($codes);
        usort($list, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));

        return $list !== [] ? $list : ['50', '55', '70', '77', '99', '10', '51'];
    }

    public static function subscriberDigits(): int
    {
        return max(1, (int) config('homeservice.phone_subscriber_digits', 7));
    }

    /**
     * Digits after country prefix (operator + subscriber), e.g. 9 for AZ.
     */
    public static function nationalLength(): int
    {
        $ops = self::operatorCodes();
        $opLen = max(array_map('strlen', $ops) ?: [2]);

        return $opLen + self::subscriberDigits();
    }

    /**
     * Normalize user input to E.164-ish form with configured prefix when possible.
     */
    public static function normalize(string $phone): string
    {
        $phone = trim(preg_replace('/[\s\-()]/', '', $phone) ?? '');
        if (str_starts_with($phone, '00')) {
            $phone = '+'.substr($phone, 2);
        }

        $prefix = self::prefix();
        $prefixDigits = preg_replace('/\D+/', '', $prefix) ?: '994';

        if (preg_match('/^0\d+$/', $phone) === 1) {
            // 050XXXXXXX → +99450XXXXXXX
            $phone = $prefix.substr($phone, 1);
        } elseif (preg_match('/^\+?'.$prefixDigits.'\d+$/', $phone) === 1) {
            $digits = preg_replace('/\D+/', '', $phone) ?: '';
            $phone = '+'.$digits;
        } elseif (preg_match('/^\d{'.self::nationalLength().'}$/', $phone) === 1) {
            $phone = $prefix.$phone;
        }

        if (str_starts_with($phone, $prefixDigits) && ! str_starts_with($phone, '+')) {
            $phone = '+'.$phone;
        }

        return $phone;
    }

    public static function isValid(string $phone): bool
    {
        $normalized = self::normalize($phone);
        $prefix = self::prefix();
        if (! str_starts_with($normalized, $prefix)) {
            return false;
        }

        $rest = substr($normalized, strlen($prefix));
        if ($rest === false || preg_match('/^\d+$/', $rest) !== 1) {
            return false;
        }

        $subLen = self::subscriberDigits();
        foreach (self::operatorCodes() as $op) {
            if (str_starts_with($rest, $op) && strlen($rest) === strlen($op) + $subLen) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{prefix: string, operator_codes: list<string>, subscriber_digits: int, national_length: int}
     */
    public static function bootstrapConfig(): array
    {
        return [
            'prefix' => self::prefix(),
            'operator_codes' => self::operatorCodes(),
            'subscriber_digits' => self::subscriberDigits(),
            'national_length' => self::nationalLength(),
        ];
    }
}
