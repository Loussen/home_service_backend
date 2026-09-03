<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Category;
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

        $rawPath = trim($request->path(), '/');
        $path = $this->normalizeApiPath($rawPath);
        if ($path === 'v1/health' || $path === 'up' || str_starts_with($path, 'up/')) {
            return;
        }

        $user = $request->user();
        if (! $user instanceof User && ! str_starts_with($path, 'v1/auth/otp/')) {
            return;
        }

        $action = $this->resolveAction($request->method(), $path, $request);
        $properties = $this->buildProperties($request, $action['key']);

        $this->write([
            'user_id' => $user?->id,
            'action' => $action['key'],
            'label' => $action['label'],
            'method' => strtoupper($request->method()),
            'path' => '/'.$rawPath,
            'status_code' => $response->getStatusCode(),
            'platform' => $this->resolvePlatform($request),
            'ip' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
            'properties' => $properties ?: null,
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

    private function normalizeApiPath(string $path): string
    {
        $path = trim($path, '/');
        if (str_starts_with($path, 'api/')) {
            $path = substr($path, 4);
        }

        return trim($path, '/');
    }

    /**
     * @return array{key: string, label: string}
     */
    private function resolveAction(string $method, string $path, Request $request): array
    {
        $m = strtoupper($method);
        $exact = $m.' '.$path;

        $map = [
            'POST v1/auth/otp/send' => ['auth.otp_send', 'OTP kodu göndərildi'],
            'POST v1/auth/otp/verify' => ['auth.otp_verify', 'Sistemə daxil oldu (OTP)'],
            'POST v1/auth/logout' => ['auth.logout', 'Sistemdən çıxdı'],
            'POST v1/auth/role' => ['auth.role', 'Rol seçdi'],
            'PATCH v1/auth/profile' => ['auth.profile_update', 'Ad / profil məlumatını yenilədi'],
            'POST v1/auth/avatar' => ['auth.avatar', 'Profil şəkli yüklədi'],
            'POST v1/auth/provider/resubmit-review' => ['auth.provider_resubmit', 'Yenidən baxışa göndərdi'],
            'POST v1/service-requests/text' => ['request.text', 'Mətnlə xidmət sorğusu yaratdı'],
            'POST v1/service-requests/audio' => ['request.audio', 'Səslə xidmət sorğusu yaratdı'],
            'POST v1/conversations' => ['chat.connect', 'CONNECT etdi / söhbət açdı'],
            'POST v1/conversations/reply' => ['chat.reply', 'Gələn işə cavab verdi'],
            'POST v1/wallet/top-up' => ['wallet.top_up', 'Balansı artırdı'],
            'POST v1/provider-profiles' => ['provider.profile_create', 'Xidmətçi profili yaratdı'],
            'POST v1/device-tokens' => ['device.register', 'Bildiriş cihazını qeyd etdi'],
            'DELETE v1/device-tokens' => ['device.unregister', 'Bildiriş cihazını sildi'],
            'POST v1/reports' => ['moderation.report', 'Şikayət göndərdi'],
            'POST v1/verification-documents' => ['provider.verification', 'Təsdiq sənədi yüklədi'],
        ];

        if (isset($map[$exact])) {
            $resolved = ['key' => $map[$exact][0], 'label' => $map[$exact][1]];
        } elseif (preg_match('#^POST v1/service-requests/(\d+)/urgent$#', $exact)) {
            $resolved = ['key' => 'request.urgent', 'label' => 'Sorğunu təcili etdi'];
        } elseif (preg_match('#^POST v1/service-requests/(\d+)/bump$#', $exact)) {
            $resolved = ['key' => 'request.bump', 'label' => 'Sorğunu önə çıxartdı (bump)'];
        } elseif (preg_match('#^PUT v1/provider-profiles/(\d+)$#', $exact)
            || preg_match('#^PATCH v1/provider-profiles/(\d+)$#', $exact)) {
            $resolved = ['key' => 'provider.profile_update', 'label' => 'Xidmətçi profilini yenilədi'];
        } elseif (preg_match('#^DELETE v1/provider-profiles/(\d+)$#', $exact)) {
            $resolved = ['key' => 'provider.profile_delete', 'label' => 'Xidmətçi profilini sildi'];
        } elseif (preg_match('#^POST v1/provider-profiles/(\d+)/audio-intro$#', $exact)) {
            $resolved = ['key' => 'provider.audio', 'label' => 'Audio intro yüklədi'];
        } elseif (preg_match('#^POST v1/provider-profiles/(\d+)/bump$#', $exact)) {
            $resolved = ['key' => 'provider.bump', 'label' => 'Profilini önə çıxartdı (bump)'];
        } elseif (preg_match('#^POST v1/provider-profiles/(\d+)/vip$#', $exact)) {
            $resolved = ['key' => 'provider.vip', 'label' => 'VIP status aldı'];
        } elseif (preg_match('#^POST v1/conversations/(\d+)/messages$#', $exact)) {
            $resolved = ['key' => 'chat.message', 'label' => 'Chat-ə mesaj yazdı'];
        } elseif (preg_match('#^POST v1/conversations/(\d+)/offers$#', $exact)) {
            $resolved = ['key' => 'chat.offer', 'label' => 'Qiymət təklifi göndərdi'];
        } elseif (preg_match('#^POST v1/offers/(\d+)/accept$#', $exact)) {
            $resolved = ['key' => 'offer.accept', 'label' => 'Təklifi qəbul etdi'];
        } elseif (preg_match('#^POST v1/offers/(\d+)/decline$#', $exact)) {
            $resolved = ['key' => 'offer.decline', 'label' => 'Təklifi rədd etdi'];
        } elseif (preg_match('#^POST v1/offers/(\d+)/complete$#', $exact)) {
            $resolved = ['key' => 'offer.complete', 'label' => 'İşi tamamlandı kimi qeyd etdi'];
        } elseif (preg_match('#^POST v1/offers/(\d+)/cancel$#', $exact)) {
            $resolved = ['key' => 'offer.cancel', 'label' => 'Təklifi / işi ləğv etdi'];
        } elseif (preg_match('#^POST v1/offers/(\d+)/reviews$#', $exact)) {
            $resolved = ['key' => 'review.create', 'label' => 'Rəy yazdı'];
        } elseif (preg_match('#^POST v1/users/(\d+)/block$#', $exact)) {
            $resolved = ['key' => 'moderation.block', 'label' => 'İstifadəçini blokladı'];
        } elseif (preg_match('#^DELETE v1/users/(\d+)/block$#', $exact)) {
            $resolved = ['key' => 'moderation.unblock', 'label' => 'Bloku götürdü'];
        } elseif (preg_match('#^POST v1/bookings/(\d+)/cancel$#', $exact)) {
            $resolved = ['key' => 'booking.cancel', 'label' => 'Bronu ləğv etdi'];
        } else {
            $resolved = [
                'key' => 'api.'.strtolower($m),
                'label' => 'Digər əməliyyat ('.$m.' '.$path.')',
            ];
        }

        // Kateqoriya dəyişikliyi ayrıca izah olunsun
        if (
            in_array($resolved['key'], ['provider.profile_update', 'provider.profile_create'], true)
            && $request->filled('category_ids')
        ) {
            $names = $this->categoryNames((array) $request->input('category_ids', []));
            $resolved = [
                'key' => 'provider.categories',
                'label' => $names !== []
                    ? 'Kateqoriyalarını dəyişdi: '.implode(', ', $names)
                    : 'Kateqoriyalarını dəyişdi',
            ];
        }

        if ($resolved['key'] === 'auth.role') {
            $role = (string) $request->input('role', '');
            $resolved['label'] = match ($role) {
                'provider' => 'Rol seçdi: İcraçı (xidmətçi)',
                'client' => 'Rol seçdi: Ailə (müştəri)',
                default => 'Rol seçdi',
            };
        }

        return $resolved;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildProperties(Request $request, string $actionKey): array
    {
        $props = [];

        if ($request->filled('phone')) {
            $props['Telefon'] = (string) $request->input('phone');
        }
        if ($request->filled('role')) {
            $props['Rol'] = match ((string) $request->input('role')) {
                'provider' => 'İcraçı',
                'client' => 'Ailə',
                default => (string) $request->input('role'),
            };
        }
        if ($request->filled('name')) {
            $props['Ad'] = (string) $request->input('name');
        }
        if ($request->filled('category_ids')) {
            $ids = array_values(array_filter(array_map('intval', (array) $request->input('category_ids', []))));
            $names = $this->categoryNames($ids);
            $props['Kateqoriyalar'] = $names !== [] ? implode(', ', $names) : implode(', ', $ids);
            $props['category_ids'] = $ids;
        }
        if ($request->filled('title')) {
            $props['Başlıq'] = (string) $request->input('title');
        }
        if ($request->filled('city')) {
            $props['Şəhər'] = (string) $request->input('city');
        }
        if ($request->filled('district')) {
            $props['Rayon'] = (string) $request->input('district');
        }
        if ($request->filled('amount')) {
            $props['Məbləğ'] = $request->input('amount').' AZN';
        }

        return $props;
    }

    /**
     * @param  list<int|string>  $ids
     * @return list<string>
     */
    private function categoryNames(array $ids): array
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if ($ids === []) {
            return [];
        }

        try {
            return Category::query()
                ->whereIn('id', $ids)
                ->orderBy('name_az')
                ->pluck('name_az')
                ->filter()
                ->values()
                ->all();
        } catch (Throwable) {
            return [];
        }
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
