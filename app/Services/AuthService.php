<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\User;
use App\Notifications\NewProviderPendingApproval;
use App\Repositories\OtpRepository;
use App\Repositories\UserRepository;
use App\Services\ActivityLogger;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function __construct(
        private readonly OtpRepository $otpRepository,
        private readonly UserRepository $userRepository,
        private readonly WalletService $walletService,
    ) {}

    public function sendOtp(string $phone): array
    {
        $window = (int) config('homeservice.otp_send_window_minutes', 15);
        $maxSends = (int) config('homeservice.otp_send_max', 3);
        $resendAfter = (int) config('homeservice.otp_resend_seconds', 30);

        if ($this->otpRepository->recentSendCount($phone, $window) >= $maxSends) {
            throw ValidationException::withMessages([
                'phone' => ["Çox cəhd. {$window} dəqiqə sonra yenidən yoxlayın."],
            ]);
        }

        $since = $this->otpRepository->secondsSinceLastSend($phone);
        if ($since !== null && $since < $resendAfter) {
            $wait = $resendAfter - $since;
            throw ValidationException::withMessages([
                'phone' => ["Yeni kod üçün {$wait} saniyə gözləyin."],
            ]);
        }

        $debugOk = ! app()->environment('production')
            && (bool) config('homeservice.otp_allow_debug_code', true);
        $code = $debugOk
            ? '123456'
            : (string) random_int(100000, 999999);

        $this->otpRepository->create(
            $phone,
            $code,
            (int) config('homeservice.otp_ttl_minutes', 5)
        );

        if (config('homeservice.otp_driver') === 'log') {
            Log::info('OTP sent', ['phone' => $phone, 'code' => $code]);
        }

        return [
            'phone' => $phone,
            'expires_in' => (int) config('homeservice.otp_ttl_minutes', 5) * 60,
            'debug_code' => $debugOk ? $code : null,
        ];
    }

    public function verifyOtp(string $phone, string $code): array
    {
        $otp = $this->otpRepository->latestPending($phone);

        if (! $otp) {
            throw ValidationException::withMessages([
                'code' => ['OTP not found. Please request a new code.'],
            ]);
        }

        if ($otp->isExpired()) {
            throw ValidationException::withMessages([
                'code' => ['OTP has expired.'],
            ]);
        }

        if ($otp->attempts >= (int) config('homeservice.otp_max_attempts', 5)) {
            throw ValidationException::withMessages([
                'code' => ['Too many failed attempts.'],
            ]);
        }

        if ($otp->code !== $code) {
            $this->otpRepository->incrementAttempts($otp);

            throw ValidationException::withMessages([
                'code' => ['Invalid OTP code.'],
            ]);
        }

        $this->otpRepository->markVerified($otp);

        $user = $this->userRepository->findByPhone($phone);
        $isNew = false;

        if (! $user) {
            $isNew = true;
            $user = $this->userRepository->create([
                'phone' => $phone,
                'phone_verified_at' => now(),
                'active_role' => 'client',
                'role_chosen_at' => null,
            ]);
        } else {
            $this->userRepository->update($user, [
                'phone_verified_at' => now(),
            ]);
        }

        if ($user->isBlocked()) {
            throw ValidationException::withMessages([
                'phone' => ['This account is blocked.'],
            ]);
        }

        $token = $user->createToken('mobile')->plainTextToken;

        return [
            'token' => $token,
            'token_type' => 'Bearer',
            'is_new_user' => $isNew,
            'user' => $user->fresh(),
        ];
    }

    public function setRole(User $user, string $role): User
    {
        if ($user->role_chosen_at !== null) {
            abort_if($user->active_role !== $role, 422, 'Rol artıq seçilib və dəyişdirilə bilməz');

            return $user;
        }

        $payload = [
            'active_role' => $role,
            'role_chosen_at' => now(),
        ];

        if ($role === 'provider') {
            $payload['provider_approval_status'] = 'pending';
            $payload['provider_approved_at'] = null;
            $payload['provider_approved_by'] = null;
            $payload['provider_rejection_note'] = null;
        } else {
            $payload['provider_approval_status'] = null;
            $payload['provider_approved_at'] = null;
            $payload['provider_approved_by'] = null;
            $payload['provider_rejection_note'] = null;
        }

        $user = $this->userRepository->update($user, $payload);

        if ($role === 'provider' && ! $user->welcome_bonus_granted) {
            $this->walletService->grantWelcomeBonus($user);
            $user = $user->fresh();
        }

        if ($role === 'provider') {
            $this->notifyAdminsOfPendingProvider($user);
        }

        return $user;
    }

    public function updateProfile(User $user, array $data): User
    {
        return $this->userRepository->update($user, array_filter([
            'name' => $data['name'] ?? null,
            'avatar_url' => $data['avatar_url'] ?? null,
        ], fn ($v) => $v !== null));
    }

    public function uploadAvatar(User $user, UploadedFile $avatar): User
    {
        if ($user->avatar_url && ! str_starts_with($user->avatar_url, 'http')) {
            if (Storage::disk('public')->exists($user->avatar_url)) {
                Storage::disk('public')->delete($user->avatar_url);
            }
        }

        $path = $avatar->store("avatars/{$user->id}", 'public');

        return $this->userRepository->update($user, [
            'avatar_url' => $path,
        ]);
    }

    public function approveProvider(User $user, ?Admin $admin = null): User
    {
        abort_unless($user->isProvider(), 422, 'Yalnız icraçı təsdiqlənə bilər');

        $user = $this->userRepository->update($user, [
            'provider_approval_status' => 'approved',
            'provider_approved_at' => now(),
            'provider_approved_by' => $admin?->id,
            'provider_rejection_note' => null,
        ]);

        app(ActivityLogger::class)->record(
            $user,
            'admin.provider_approve',
            'İcraçı təsdiqləndi',
            ['admin_id' => $admin?->id],
            'admin',
        );

        return $user;
    }

    public function rejectProvider(User $user, ?string $note = null, ?Admin $admin = null): User
    {
        abort_unless($user->isProvider(), 422, 'Yalnız icraçı rədd edilə bilər');

        $user = $this->userRepository->update($user, [
            'provider_approval_status' => 'rejected',
            'provider_approved_at' => null,
            'provider_approved_by' => $admin?->id,
            'provider_rejection_note' => $note,
        ]);

        app(ActivityLogger::class)->record(
            $user,
            'admin.provider_reject',
            'İcraçı rədd edildi',
            ['admin_id' => $admin?->id, 'note' => $note],
            'admin',
        );

        return $user;
    }

    private function notifyAdminsOfPendingProvider(User $provider): void
    {
        try {
            Admin::query()
                ->where('is_active', true)
                ->get()
                ->each(fn (Admin $admin) => $admin->notify(new NewProviderPendingApproval($provider)));
        } catch (\Throwable $e) {
            Log::warning('Provider pending notification failed', [
                'provider_id' => $provider->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
