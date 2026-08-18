<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class FcmClient
{
    public function isConfigured(): bool
    {
        $c = $this->credentials();

        return filled($c['project_id'] ?? null)
            && filled($c['client_email'] ?? null)
            && filled($c['private_key'] ?? null);
    }

    /**
     * @param  array<string, string>  $notification  title, body
     * @param  array<string, string>  $data
     * @return array{ok: bool, unregistered: bool, error: ?string}
     */
    public function send(string $token, array $notification, array $data = []): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'unregistered' => false, 'error' => 'fcm_not_configured'];
        }

        $projectId = $this->credentials()['project_id'];

        try {
            $response = Http::withToken($this->accessToken())
                ->acceptJson()
                ->timeout(12)
                ->post(
                    "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send",
                    [
                        'message' => [
                            'token' => $token,
                            'notification' => [
                                'title' => $notification['title'] ?? '',
                                'body' => $notification['body'] ?? '',
                            ],
                            'data' => $data,
                            'android' => [
                                'priority' => 'high',
                            ],
                            'apns' => [
                                'headers' => [
                                    'apns-priority' => '10',
                                ],
                                'payload' => [
                                    'aps' => [
                                        'sound' => 'default',
                                        'badge' => 1,
                                    ],
                                ],
                            ],
                        ],
                    ]
                );
        } catch (\Throwable $e) {
            Log::warning('FCM HTTP failed', ['error' => $e->getMessage()]);

            return ['ok' => false, 'unregistered' => false, 'error' => $e->getMessage()];
        }

        if ($response->successful()) {
            return ['ok' => true, 'unregistered' => false, 'error' => null];
        }

        return [
            'ok' => false,
            'unregistered' => $this->isUnregistered($response),
            'error' => $response->json('error.message') ?: $response->body(),
        ];
    }

    private function isUnregistered(Response $response): bool
    {
        if (in_array($response->status(), [404, 410], true)) {
            return true;
        }

        $details = $response->json('error.details') ?? [];
        foreach ($details as $detail) {
            if (($detail['errorCode'] ?? null) === 'UNREGISTERED') {
                return true;
            }
        }

        $status = (string) $response->json('error.status');

        return $status === 'NOT_FOUND';
    }

    private function accessToken(): string
    {
        $cacheKey = 'fcm_access_token_v1';
        $cached = Cache::get($cacheKey);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $jwt = $this->jwt();
        $response = Http::asForm()
            ->timeout(12)
            ->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('FCM OAuth failed: '.($response->json('error_description') ?: $response->body()));
        }

        $token = (string) $response->json('access_token');
        $expires = max(60, ((int) $response->json('expires_in', 3600)) - 120);
        Cache::put($cacheKey, $token, $expires);

        return $token;
    }

    private function jwt(): string
    {
        $c = $this->credentials();
        $now = time();
        $header = $this->b64url(json_encode(['alg' => 'RS256', 'typ' => 'JWT'], JSON_THROW_ON_ERROR));
        $payload = $this->b64url(json_encode([
            'iss' => $c['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ], JSON_THROW_ON_ERROR));

        $unsigned = $header.'.'.$payload;
        $key = openssl_pkey_get_private((string) $c['private_key']);
        if ($key === false) {
            throw new RuntimeException('FCM private key is invalid');
        }

        $ok = openssl_sign($unsigned, $signature, $key, OPENSSL_ALGO_SHA256);
        if (! $ok) {
            throw new RuntimeException('FCM JWT sign failed');
        }

        return $unsigned.'.'.$this->b64url($signature);
    }

    /**
     * @return array{project_id?: string, client_email?: string, private_key?: string}
     */
    private function credentials(): array
    {
        $path = (string) config('homeservice.fcm.credentials', '');
        if ($path !== '' && is_file($path)) {
            $json = json_decode((string) file_get_contents($path), true);

            return is_array($json) ? $json : [];
        }

        $key = str_replace('\\n', "\n", (string) config('homeservice.fcm.private_key', ''));

        return [
            'project_id' => (string) config('homeservice.fcm.project_id', ''),
            'client_email' => (string) config('homeservice.fcm.client_email', ''),
            'private_key' => $key,
        ];
    }

    private function b64url(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }
}
