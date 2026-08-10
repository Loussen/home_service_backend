<?php

namespace App\Repositories;

use App\Models\PhoneOtp;
use Carbon\Carbon;

class OtpRepository
{
    public function create(string $phone, string $code, int $ttlMinutes = 5): PhoneOtp
    {
        PhoneOtp::where('phone', $phone)
            ->whereNull('verified_at')
            ->delete();

        return PhoneOtp::create([
            'phone' => $phone,
            'code' => $code,
            'expires_at' => Carbon::now()->addMinutes($ttlMinutes),
        ]);
    }

    public function latestPending(string $phone): ?PhoneOtp
    {
        return PhoneOtp::where('phone', $phone)
            ->whereNull('verified_at')
            ->latest()
            ->first();
    }

    public function markVerified(PhoneOtp $otp): void
    {
        $otp->update(['verified_at' => now()]);
    }

    public function incrementAttempts(PhoneOtp $otp): void
    {
        $otp->increment('attempts');
    }
}
