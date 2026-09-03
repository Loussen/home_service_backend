<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ActivityLogger
{
    public function fromRequest(Request $request, Response $response): void
    {
        if (! in_array(strtoupper($request->method()), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return;
        }

        $path = trim($request->path(), '/');
        if ($path === 'v1/health' || str_starts_with($path, 'up')) {
            return;
        }

        $user = $request->user();
        if (! $user instanceof User && ! str_starts_with($path, 'v1/auth/otp/')) {
            return;
        }

        $action = $this->resolveAction($request->method(), $path);
        $this->write([
            'user_id' => $user?->id,
            'action' => $action['key'],
            'label' => $action['label'],
            'method' => strtoupper($request->method()),
            'path' => '/'.$path,
            'status_code' => $response->getStatusCode(),
            'platform' => $this->resolvePlatform($request),
            'ip' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
            'properties' => array_filter([
                'phone' => $request->input('phone'),
                'role' => $request->input('role'),
            ]),
        ]);
    }

    public function record(
        ?User $user,
        string $action,
        string $label,
        array $properties = [],
        string $platform = 'system',
    ): void {
        $this->write([
            'user_id' => $user?->id,
            'action' => $action,
            'label' => $label,
            'method' => null,
            'path' => null,
            'status_code' => null,
            'platform' => $platform,
            'ip' => request()?->ip(),
            'user_agent' => request() ? mb_substr((string) request()->userAgent(), 0, 500) : null,
            'properties' => $properties ?: null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function write(array $payload): void
    {
        try {
            ActivityLog::query()->create($payload);
        } catch (Throwable) {
            // Logging must never break the main request.
        }
    }

    /**
     * @return array{key: string, label: string}
     */
    private function resolveAction(string $method, string $path): array
    {
        $m = strtoupper($method);
        $map = [
            'POST v1/auth/otp/send' => ['auth.otp_send', 'OTP göndərildi'],
            'POST v1/auth/otp/verify' => ['auth.otp_verify', 'Giriş (OTP təsdiq)'],
            'POST v1/auth/logout' => ['auth.logout', 'Çıxış'],
            'POST v1/auth/role' => ['auth.role', 'Rol seçildi'],
            'PATCH v1/auth/profile' => ['auth.profile_update', 'Profil yeniləndi'],
            'POST v1/auth/avatar' => ['auth.avatar', 'Profil şəkli yükləndi'],
            'POST v1/service-requests/text' => ['request.text', 'Mətn sorğusu yaradıldı'],
            'POST v1/service-requests/audio' => ['request.audio', 'Səs sorğusu yaradıldı'],
            'POST v1/conversations' => ['chat.connect', 'CONNECT / söhbət açıldı'],
            'POST v1/conversations/reply' => ['chat.reply', 'İşə cavab verildi'],
            'POST v1/wallet/top-up' => ['wallet.top_up', 'Balans artırıldı'],
            'POST v1/provider-profiles' => ['provider.profile_create', 'Xidmətçi profili yaradıldı'],
            'POST v1/device-tokens' => ['device.register', 'Cihaz token qeyd'],
            'DELETE v1/device-tokens' => ['device.unregister', 'Cihaz token silindi'],
            'POST v1/reports' => ['moderation.report', 'Şikayət göndərildi'],
            'POST v1/verification-documents' => ['provider.verification', 'Təsdiq sənədi yükləndi'],
        ];

        $exact = $m.' '.$path;
        if (isset($map[$exact])) {
            return ['key' => $map[$exact][0], 'label' => $map[$exact][1]];
        }

        if (preg_match('#^POST v1/service-requests/(\d+)/urgent$#', $exact)) {
            return ['key' => 'request.urgent', 'label' => 'Təcili sorğu'];
        }
        if (preg_match('#^POST v1/service-requests/(\d+)/bump$#', $exact)) {
            return ['key' => 'request.bump', 'label' => 'Sorğu bump'];
        }
        if (preg_match('#^PUT v1/provider-profiles/(\d+)$#', $exact)) {
            return ['key' => 'provider.profile_update', 'label' => 'Xidmətçi profili yeniləndi'];
        }
        if (preg_match('#^DELETE v1/provider-profiles/(\d+)$#', $exact)) {
            return ['key' => 'provider.profile_delete', 'label' => 'Xidmətçi profili silindi'];
        }
        if (preg_match('#^POST v1/provider-profiles/(\d+)/audio-intro$#', $exact)) {
            return ['key' => 'provider.audio', 'label' => 'Audio intro yükləndi'];
        }
        if (preg_match('#^POST v1/provider-profiles/(\d+)/bump$#', $exact)) {
            return ['key' => 'provider.bump', 'label' => 'Profil bump'];
        }
        if (preg_match('#^POST v1/provider-profiles/(\d+)/vip$#', $exact)) {
            return ['key' => 'provider.vip', 'label' => 'VIP aktivləşdi'];
        }
        if (preg_match('#^POST v1/conversations/(\d+)/messages$#', $exact)) {
            return ['key' => 'chat.message', 'label' => 'Mesaj göndərildi'];
        }
        if (preg_match('#^POST v1/conversations/(\d+)/offers$#', $exact)) {
            return ['key' => 'chat.offer', 'label' => 'Təklif göndərildi'];
        }
        if (preg_match('#^POST v1/offers/(\d+)/accept$#', $exact)) {
            return ['key' => 'offer.accept', 'label' => 'Təklif qəbul'];
        }
        if (preg_match('#^POST v1/offers/(\d+)/decline$#', $exact)) {
            return ['key' => 'offer.decline', 'label' => 'Təklif rədd'];
        }
        if (preg_match('#^POST v1/offers/(\d+)/complete$#', $exact)) {
            return ['key' => 'offer.complete', 'label' => 'İş tamamlandı'];
        }
        if (preg_match('#^POST v1/offers/(\d+)/cancel$#', $exact)) {
            return ['key' => 'offer.cancel', 'label' => 'Təklif ləğv'];
        }
        if (preg_match('#^POST v1/offers/(\d+)/reviews$#', $exact)) {
            return ['key' => 'review.create', 'label' => 'Rəy yazıldı'];
        }
        if (preg_match('#^POST v1/users/(\d+)/block$#', $exact)) {
            return ['key' => 'moderation.block', 'label' => 'İstifadəçi bloklandı'];
        }
        if (preg_match('#^DELETE v1/users/(\d+)/block$#', $exact)) {
            return ['key' => 'moderation.unblock', 'label' => 'Blok götürüldü'];
        }
        if (preg_match('#^POST v1/bookings/(\d+)/cancel$#', $exact)) {
            return ['key' => 'booking.cancel', 'label' => 'Bron ləğv'];
        }

        return [
            'key' => 'api.'.strtolower($m),
            'label' => $m.' /'.$path,
        ];
    }

    private function resolvePlatform(Request $request): string
    {
        $header = strtolower((string) $request->header('X-Client', ''));
        if (in_array($header, ['web', 'app', 'admin', 'system'], true)) {
            return $header;
        }

        $ua = strtolower((string) $request->userAgent());
        if (str_contains($ua, 'okhttp') || str_contains($ua, 'dart:') || str_contains($ua, 'flutter')) {
            return 'app';
        }
        if ($ua !== '') {
            return 'web';
        }

        return 'unknown';
    }
}
