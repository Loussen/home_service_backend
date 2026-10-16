<?php

namespace App\Services;

use App\Models\IapPurchase;
use App\Models\Transaction;
use App\Models\User;
use App\Repositories\TransactionRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class IapService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly TransactionRepository $transactionRepository,
    ) {}

    /**
     * @return list<array{product_id: string, pay: float, credit: float}>
     */
    public function products(): array
    {
        $raw = config('homeservice.wallet_iap_products', []);
        $out = [];
        foreach ($raw as $row) {
            if (! is_array($row) || empty($row['product_id'])) {
                continue;
            }
            $out[] = [
                'product_id' => (string) $row['product_id'],
                'pay' => (float) ($row['pay'] ?? 0),
                'credit' => (float) ($row['credit'] ?? $row['pay'] ?? 0),
            ];
        }

        return $out;
    }

    public function productById(string $productId): ?array
    {
        foreach ($this->products() as $p) {
            if ($p['product_id'] === $productId) {
                return $p;
            }
        }

        return null;
    }

    /**
     * Verify store purchase and credit wallet (idempotent on platform+token).
     *
     * @return array{user: User, balance: float, credit: float, already_credited: bool}
     */
    public function confirm(
        User $user,
        string $platform,
        string $productId,
        string $verificationData,
        ?string $purchaseId = null,
    ): array {
        $product = $this->productById($productId);
        if ($product === null) {
            throw ValidationException::withMessages([
                'product_id' => ['Naməlum paket.'],
            ]);
        }

        $verified = $platform === 'ios'
            ? $this->verifyApple($productId, $verificationData, $purchaseId)
            : $this->verifyGoogle($productId, $verificationData);

        $token = (string) $verified['purchase_token'];
        if ($token === '') {
            throw ValidationException::withMessages([
                'verification_data' => ['Satınalma təsdiqlənmədi.'],
            ]);
        }

        $existing = IapPurchase::query()
            ->where('platform', $platform)
            ->where('purchase_token', $token)
            ->first();

        if ($existing) {
            if ((int) $existing->user_id !== (int) $user->id) {
                throw ValidationException::withMessages([
                    'verification_data' => ['Bu satınalma artıq başqa hesaba bağlıdır.'],
                ]);
            }

            return [
                'user' => $user->fresh() ?? $user,
                'balance' => (float) ($user->fresh()?->balance ?? $user->balance),
                'credit' => (float) $existing->credit_amount,
                'already_credited' => true,
            ];
        }

        $credit = (float) $product['credit'];
        $pay = (float) $product['pay'];
        $method = $platform === 'ios' ? 'iap_apple' : 'iap_google';

        return DB::transaction(function () use ($user, $platform, $productId, $token, $credit, $pay, $method, $verified) {
            $locked = User::query()->lockForUpdate()->findOrFail($user->id);

            // Race: another request may have inserted while we waited.
            $again = IapPurchase::query()
                ->where('platform', $platform)
                ->where('purchase_token', $token)
                ->lockForUpdate()
                ->first();
            if ($again) {
                return [
                    'user' => $locked->fresh() ?? $locked,
                    'balance' => (float) $locked->balance,
                    'credit' => (float) $again->credit_amount,
                    'already_credited' => true,
                ];
            }

            $fresh = $this->userRepository->creditBalance($locked, $credit);
            /** @var Transaction $tx */
            $tx = $this->transactionRepository->create([
                'user_id' => $fresh->id,
                'amount' => $credit,
                'type' => 'top_up',
                'payment_method' => $method,
                'status' => 'completed',
                'reference' => $platform.':'.$token,
                'meta' => [
                    'product_id' => $productId,
                    'pay' => $pay,
                    'store' => $verified['store'] ?? $platform,
                    'unverified' => (bool) ($verified['unverified'] ?? false),
                ],
            ]);

            IapPurchase::query()->create([
                'user_id' => $fresh->id,
                'platform' => $platform,
                'product_id' => $productId,
                'purchase_token' => $token,
                'credit_amount' => $credit,
                'pay_amount' => $pay,
                'status' => 'completed',
                'transaction_id' => $tx->id,
                'meta' => [
                    'purchase_id' => $verified['purchase_id'] ?? null,
                    'unverified' => (bool) ($verified['unverified'] ?? false),
                ],
            ]);

            return [
                'user' => $fresh,
                'balance' => (float) $fresh->balance,
                'credit' => $credit,
                'already_credited' => false,
            ];
        });
    }

    /**
     * @return array{purchase_token: string, purchase_id?: string, store: string, unverified?: bool}
     */
    private function verifyApple(string $productId, string $receiptData, ?string $purchaseId): array
    {
        // StoreKit 2 / in_app_purchase often sends JWS (header.payload.sig).
        if (substr_count($receiptData, '.') === 2) {
            return $this->verifyAppleJws($productId, $receiptData, $purchaseId);
        }

        $secret = (string) config('homeservice.apple_iap_shared_secret', '');
        if ($secret === '') {
            return $this->unverifiedFallback('ios', $productId, $receiptData, $purchaseId);
        }

        $payload = [
            'receipt-data' => $receiptData,
            'password' => $secret,
            'exclude-old-transactions' => true,
        ];

        $response = $this->appleVerify('https://buy.itunes.apple.com/verifyReceipt', $payload);
        $status = (int) ($response['status'] ?? -1);
        if ($status === 21007) {
            $response = $this->appleVerify('https://sandbox.itunes.apple.com/verifyReceipt', $payload);
            $status = (int) ($response['status'] ?? -1);
        }

        if ($status !== 0) {
            Log::warning('Apple IAP verify failed', ['status' => $status, 'product' => $productId]);
            throw ValidationException::withMessages([
                'verification_data' => ['Apple satınalması təsdiqlənmədi.'],
            ]);
        }

        $bundle = (string) config('homeservice.apple_iap_bundle_id', '');
        $receiptBundle = (string) data_get($response, 'receipt.bundle_id', '');
        if ($bundle !== '' && $receiptBundle !== '' && $receiptBundle !== $bundle) {
            throw ValidationException::withMessages([
                'verification_data' => ['Yanlış tətbiq qəbzi.'],
            ]);
        }

        $candidates = array_merge(
            data_get($response, 'latest_receipt_info', []) ?: [],
            data_get($response, 'receipt.in_app', []) ?: [],
        );

        $match = null;
        foreach ($candidates as $row) {
            if (! is_array($row)) {
                continue;
            }
            if ((string) ($row['product_id'] ?? '') !== $productId) {
                continue;
            }
            if ($purchaseId !== null && $purchaseId !== '' && (string) ($row['transaction_id'] ?? '') !== $purchaseId
                && (string) ($row['original_transaction_id'] ?? '') !== $purchaseId) {
                continue;
            }
            $match = $row;
            break;
        }

        if ($match === null && $purchaseId) {
            foreach ($candidates as $row) {
                if (! is_array($row)) {
                    continue;
                }
                if ((string) ($row['transaction_id'] ?? '') === $purchaseId
                    || (string) ($row['original_transaction_id'] ?? '') === $purchaseId) {
                    if ((string) ($row['product_id'] ?? '') === $productId) {
                        $match = $row;
                        break;
                    }
                }
            }
        }

        if ($match === null) {
            foreach (array_reverse($candidates) as $row) {
                if (is_array($row) && (string) ($row['product_id'] ?? '') === $productId) {
                    $match = $row;
                    break;
                }
            }
        }

        if ($match === null) {
            throw ValidationException::withMessages([
                'verification_data' => ['Qəbzdə bu paket tapılmadı.'],
            ]);
        }

        $txId = (string) ($match['transaction_id'] ?? $match['original_transaction_id'] ?? '');
        if ($txId === '') {
            throw ValidationException::withMessages([
                'verification_data' => ['Apple transaction id yoxdur.'],
            ]);
        }

        return [
            'purchase_token' => $txId,
            'purchase_id' => $txId,
            'store' => 'apple',
        ];
    }

    /**
     * StoreKit 2 JWS: look up transaction via App Store Server API.
     *
     * @return array{purchase_token: string, purchase_id?: string, store: string, unverified?: bool}
     */
    private function verifyAppleJws(string $productId, string $jws, ?string $purchaseId): array
    {
        $claims = $this->decodeJwtPayload($jws);
        $txId = (string) ($claims['transactionId'] ?? $purchaseId ?? '');
        if ($txId === '') {
            throw ValidationException::withMessages([
                'verification_data' => ['Apple transaction id yoxdur.'],
            ]);
        }

        if (! $this->appleServerApiConfigured()) {
            return $this->unverifiedFallback('ios', $productId, $jws, $txId);
        }

        $info = $this->appleGetTransaction($txId);
        $signed = (string) ($info['signedTransactionInfo'] ?? '');
        $verifiedClaims = $signed !== '' ? $this->decodeJwtPayload($signed) : $claims;

        $gotProduct = (string) ($verifiedClaims['productId'] ?? '');
        if ($gotProduct !== $productId) {
            throw ValidationException::withMessages([
                'verification_data' => ['Paket Apple tərəfində uyğun gəlmir.'],
            ]);
        }

        $bundle = (string) config('homeservice.apple_iap_bundle_id', '');
        $gotBundle = (string) ($verifiedClaims['bundleId'] ?? '');
        if ($bundle !== '' && $gotBundle !== '' && $gotBundle !== $bundle) {
            throw ValidationException::withMessages([
                'verification_data' => ['Yanlış tətbiq qəbzi.'],
            ]);
        }

        $finalTx = (string) ($verifiedClaims['transactionId'] ?? $txId);

        return [
            'purchase_token' => $finalTx,
            'purchase_id' => $finalTx,
            'store' => 'apple',
        ];
    }

    private function appleServerApiConfigured(): bool
    {
        return filled(config('homeservice.apple_iap_issuer_id'))
            && filled(config('homeservice.apple_iap_key_id'))
            && filled(config('homeservice.apple_iap_private_key'));
    }

    /**
     * @return array<string, mixed>
     */
    private function appleGetTransaction(string $transactionId): array
    {
        $token = $this->appleServerApiToken();
        $paths = [
            'https://api.storekit.itunes.apple.com/inApps/v1/transactions/'.$transactionId,
            'https://api.storekit-sandbox.itunes.apple.com/inApps/v1/transactions/'.$transactionId,
        ];

        $lastBody = '';
        foreach ($paths as $url) {
            $res = Http::withToken($token)->acceptJson()->timeout(20)->get($url);
            if ($res->successful()) {
                $json = $res->json();

                return is_array($json) ? $json : [];
            }
            $lastBody = $res->body();
            // 404 on production → try sandbox
            if ($res->status() !== 404) {
                Log::warning('Apple Server API transaction lookup failed', [
                    'status' => $res->status(),
                    'body' => $lastBody,
                ]);
            }
        }

        throw ValidationException::withMessages([
            'verification_data' => ['Apple transaction təsdiqlənmədi.'],
        ]);
    }

    private function appleServerApiToken(): string
    {
        $cacheKey = 'apple_iap_server_api_token_v1';
        $cached = Cache::get($cacheKey);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $issuer = (string) config('homeservice.apple_iap_issuer_id');
        $keyId = (string) config('homeservice.apple_iap_key_id');
        $privateKey = $this->applePrivateKeyPem();
        $now = time();
        $header = $this->b64url(json_encode([
            'alg' => 'ES256',
            'kid' => $keyId,
            'typ' => 'JWT',
        ], JSON_THROW_ON_ERROR));
        $payload = $this->b64url(json_encode([
            'iss' => $issuer,
            'iat' => $now,
            'exp' => $now + 1200,
            'aud' => 'appstoreconnect-v1',
            'bid' => (string) config('homeservice.apple_iap_bundle_id'),
        ], JSON_THROW_ON_ERROR));

        $unsigned = $header.'.'.$payload;
        $key = openssl_pkey_get_private($privateKey);
        if ($key === false) {
            throw new RuntimeException('Apple IAP .p8 private key is invalid');
        }
        $ok = openssl_sign($unsigned, $signature, $key, OPENSSL_ALGO_SHA256);
        if (! $ok) {
            throw new RuntimeException('Apple IAP JWT sign failed');
        }
        // ES256: OpenSSL returns DER; App Store expects raw R||S (64 bytes).
        $signature = $this->derToJoseEs256($signature);
        $jwt = $unsigned.'.'.$this->b64url($signature);
        Cache::put($cacheKey, $jwt, 1000);

        return $jwt;
    }

    private function applePrivateKeyPem(): string
    {
        $raw = (string) config('homeservice.apple_iap_private_key');
        if ($raw !== '' && is_file($raw)) {
            $raw = (string) file_get_contents($raw);
        }
        $raw = str_replace('\\n', "\n", $raw);
        if (! str_contains($raw, 'BEGIN PRIVATE KEY')) {
            $raw = "-----BEGIN PRIVATE KEY-----\n".trim($raw)."\n-----END PRIVATE KEY-----\n";
        }

        return $raw;
    }

    /**
     * Convert OpenSSL ECDSA DER signature to JOSE raw R||S for ES256.
     */
    private function derToJoseEs256(string $der): string
    {
        $offset = 0;
        if (! isset($der[$offset]) || ord($der[$offset++]) !== 0x30) {
            return $der;
        }
        $len = ord($der[$offset++]);
        if ($len & 0x80) {
            $offset += ($len & 0x7F);
        }
        if (! isset($der[$offset]) || ord($der[$offset++]) !== 0x02) {
            return $der;
        }
        $rLen = ord($der[$offset++]);
        $r = substr($der, $offset, $rLen);
        $offset += $rLen;
        if (! isset($der[$offset]) || ord($der[$offset++]) !== 0x02) {
            return $der;
        }
        $sLen = ord($der[$offset++]);
        $s = substr($der, $offset, $sLen);
        $r = ltrim($r, "\0");
        $s = ltrim($s, "\0");
        $r = str_pad($r, 32, "\0", STR_PAD_LEFT);
        $s = str_pad($s, 32, "\0", STR_PAD_LEFT);

        return $r.$s;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJwtPayload(string $jwt): array
    {
        $parts = explode('.', $jwt);
        if (count($parts) < 2) {
            return [];
        }
        $json = base64_decode(strtr($parts[1], '-_', '+/'), true);
        if ($json === false) {
            return [];
        }
        $data = json_decode($json, true);

        return is_array($data) ? $data : [];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function appleVerify(string $url, array $payload): array
    {
        $res = Http::timeout(20)->acceptJson()->post($url, $payload);
        if (! $res->successful()) {
            throw ValidationException::withMessages([
                'verification_data' => ['Apple verify endpoint cavab vermədi.'],
            ]);
        }

        $json = $res->json();

        return is_array($json) ? $json : [];
    }

    /**
     * @return array{purchase_token: string, purchase_id?: string, store: string, unverified?: bool}
     */
    private function verifyGoogle(string $productId, string $purchaseToken): array
    {
        $creds = $this->googleCredentials();
        if ($creds === null) {
            return $this->unverifiedFallback('android', $productId, $purchaseToken, $purchaseToken);
        }

        $package = (string) config('homeservice.android_package_name', '');
        if ($package === '') {
            throw ValidationException::withMessages([
                'platform' => ['Android package name konfiqurasiya edilməyib.'],
            ]);
        }

        $token = $this->googleAccessToken($creds);
        $url = sprintf(
            'https://androidpublisher.googleapis.com/androidpublisher/v3/applications/%s/purchases/products/%s/tokens/%s',
            rawurlencode($package),
            rawurlencode($productId),
            rawurlencode($purchaseToken),
        );

        $res = Http::withToken($token)->acceptJson()->timeout(20)->get($url);
        if (! $res->successful()) {
            Log::warning('Google IAP verify failed', [
                'status' => $res->status(),
                'body' => $res->body(),
                'product' => $productId,
            ]);
            throw ValidationException::withMessages([
                'verification_data' => ['Google satınalması təsdiqlənmədi.'],
            ]);
        }

        $json = $res->json() ?? [];
        // 0 = purchased, 1 = canceled, 2 = pending
        $purchaseState = (int) ($json['purchaseState'] ?? -1);
        if ($purchaseState !== 0) {
            throw ValidationException::withMessages([
                'verification_data' => ['Satınalma tamamlanmayıb.'],
            ]);
        }

        $orderId = (string) ($json['orderId'] ?? $purchaseToken);

        return [
            'purchase_token' => $purchaseToken,
            'purchase_id' => $orderId,
            'store' => 'google',
        ];
    }

    /**
     * @return array{purchase_token: string, purchase_id?: string, store: string, unverified: bool}
     */
    private function unverifiedFallback(
        string $platform,
        string $productId,
        string $verificationData,
        ?string $purchaseId,
    ): array {
        $allow = (bool) config('homeservice.iap_allow_unverified', false)
            || app()->environment(['local', 'testing']);

        if (! $allow || app()->environment('production')) {
            throw ValidationException::withMessages([
                'verification_data' => [
                    $platform === 'ios'
                        ? 'Apple IAP shared secret konfiqurasiya edilməyib.'
                        : 'Google Play API credentials konfiqurasiya edilməyib.',
                ],
            ]);
        }

        Log::warning('IAP credited without store verify (dev)', [
            'platform' => $platform,
            'product_id' => $productId,
        ]);

        $token = $purchaseId ?: hash('sha256', $platform.'|'.$productId.'|'.$verificationData);

        return [
            'purchase_token' => substr($token, 0, 200),
            'purchase_id' => $purchaseId,
            'store' => $platform,
            'unverified' => true,
        ];
    }

    /**
     * @return array{client_email: string, private_key: string}|null
     */
    private function googleCredentials(): ?array
    {
        $path = (string) config('homeservice.google_play_credentials', '');
        if ($path === '' || ! is_file($path)) {
            return null;
        }
        $json = json_decode((string) file_get_contents($path), true);
        if (! is_array($json)
            || empty($json['client_email'])
            || empty($json['private_key'])) {
            return null;
        }

        return [
            'client_email' => (string) $json['client_email'],
            'private_key' => (string) $json['private_key'],
        ];
    }

    /**
     * @param  array{client_email: string, private_key: string}  $creds
     */
    private function googleAccessToken(array $creds): string
    {
        $cacheKey = 'google_play_iap_access_token_v1';
        $cached = Cache::get($cacheKey);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $now = time();
        $header = $this->b64url(json_encode(['alg' => 'RS256', 'typ' => 'JWT'], JSON_THROW_ON_ERROR));
        $payload = $this->b64url(json_encode([
            'iss' => $creds['client_email'],
            'scope' => 'https://www.googleapis.com/auth/androidpublisher',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ], JSON_THROW_ON_ERROR));

        $unsigned = $header.'.'.$payload;
        $key = openssl_pkey_get_private($creds['private_key']);
        if ($key === false) {
            throw new RuntimeException('Google Play private key is invalid');
        }
        $ok = openssl_sign($unsigned, $signature, $key, OPENSSL_ALGO_SHA256);
        if (! $ok) {
            throw new RuntimeException('Google Play JWT sign failed');
        }

        $jwt = $unsigned.'.'.$this->b64url($signature);
        $response = Http::asForm()->timeout(12)->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException(
                'Google Play OAuth failed: '.($response->json('error_description') ?: $response->body())
            );
        }

        $token = (string) $response->json('access_token');
        $expires = max(60, ((int) $response->json('expires_in', 3600)) - 120);
        Cache::put($cacheKey, $token, $expires);

        return $token;
    }

    private function b64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
