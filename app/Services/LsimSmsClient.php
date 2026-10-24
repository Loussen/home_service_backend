<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * LSIM QuickSMS (https://apps.lsim.az/quicksms).
 *
 * KEY = md5( md5(password) + login + text + msisdn + sender )
 * HTTP status is always 200 — success/error is in JSON body (errorCode).
 */
class LsimSmsClient
{
    public function isConfigured(): bool
    {
        $c = config('homeservice.lsim', []);

        return filled($c['login'] ?? null)
            && filled($c['password'] ?? null)
            && filled($c['sender'] ?? null);
    }

    /**
     * @return array{ok: bool, transaction_id: int|null, error_code: int|null, error_message: ?string, raw: array}
     */
    public function send(string $msisdn, string $text, bool $unicode = true): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('LSIM SMS is not configured (LSIM_LOGIN / LSIM_PASSWORD / LSIM_SENDER).');
        }

        $login = (string) config('homeservice.lsim.login');
        $password = (string) config('homeservice.lsim.password');
        $sender = (string) config('homeservice.lsim.sender');
        $base = rtrim((string) config('homeservice.lsim.base_url', 'https://apps.lsim.az/quicksms'), '/');
        $msisdn = $this->normalizeMsisdn($msisdn);
        $key = $this->sign($password, $login, $text, $msisdn, $sender);

        $payload = [
            'login' => $login,
            'key' => $key,
            'msisdn' => $msisdn,
            'text' => $text,
            'sender' => $sender,
            'scheduled' => 'NOW',
            'unicode' => $unicode,
        ];

        $response = Http::acceptJson()
            ->timeout((int) config('homeservice.lsim.timeout', 20))
            ->asJson()
            ->post("{$base}/v1/smssender", $payload);

        $json = $response->json();
        if (! is_array($json)) {
            Log::error('LSIM SMS invalid response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw ValidationException::withMessages([
                'phone' => ['SMS göndərilmədi. Bir az sonra yenidən cəhd edin.'],
            ]);
        }

        // Doc: HTTP always 200; negative errorCode = failure. Success ≈ 0 / null + obj.
        // Newer LSIM bodies may use "message" instead of errorMessage.
        $rawCode = $json['errorCode'] ?? 0;
        $errorCode = $rawCode === null || $rawCode === '' ? 0 : (int) $rawCode;
        $transId = isset($json['obj']) && $json['obj'] !== null && $json['obj'] !== ''
            ? (int) $json['obj']
            : null;
        $errText = trim((string) ($json['errorMessage'] ?? ''));
        $altMsg = trim((string) ($json['message'] ?? ''));
        $hasErrorText = $errText !== ''
            || ($altMsg !== '' && ! preg_match('/success/i', $altMsg));
        $ok = $errorCode === 0 && ! $hasErrorText && $transId !== null;

        if (! $ok) {
            Log::warning('LSIM SMS failed', [
                'msisdn' => $msisdn,
                'sender' => $sender,
                'error_code' => $errorCode,
                'error_message' => $json['errorMessage'] ?? $json['message'] ?? null,
                'raw' => $json,
            ]);

            $providerMsg = $json['errorMessage'] ?? $json['message'] ?? null;
            if (is_string($providerMsg)) {
                $providerMsg = trim($providerMsg);
            } else {
                $providerMsg = null;
            }

            $msg = $this->userMessage($errorCode, $providerMsg);
            $msg .= ' | raw='.json_encode($json, JSON_UNESCAPED_UNICODE);

            throw ValidationException::withMessages([
                'phone' => [$msg],
            ]);
        }

        Log::info('LSIM SMS sent', [
            'msisdn' => $msisdn,
            'transaction_id' => $transId,
        ]);

        return [
            'ok' => true,
            'transaction_id' => $transId,
            'error_code' => $errorCode,
            'error_message' => null,
            'raw' => $json,
        ];
    }

    public function sendOtp(string $phone, string $code): void
    {
        $ttl = (int) config('homeservice.otp_ttl_minutes', 5);
        $template = (string) config(
            'homeservice.lsim.otp_template',
            'My Sancho kodunuz: {code}. {ttl} deqiqe etibarlidir.'
        );
        $text = str_replace(
            ['{code}', '{ttl}'],
            [$code, (string) $ttl],
            $template
        );

        // Prefer GSM-7 (unicode=false) for delivery reliability; enable unicode only if needed.
        $needsUnicode = (bool) preg_match('/[^\x00-\x7F]/', $text);
        $this->send($phone, $text, $needsUnicode);
    }

    /**
     * Delivery report for a transaction id from a successful send.
     * Status: 100 queue, 101 delivered, 102 undelivered, 103 expired, 104 rejected,
     * 105 cancelled, 106 error, 107 unknown, 108 sent, 109 blacklist.
     *
     * @return array{ok: bool, status: int|null, label: string, raw: array|int|string|null}
     */
    public function report(int|string $transactionId): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('LSIM SMS is not configured.');
        }

        $login = (string) config('homeservice.lsim.login');
        $base = rtrim((string) config('homeservice.lsim.base_url', 'https://apps.lsim.az/quicksms'), '/');

        $response = Http::acceptJson()
            ->timeout((int) config('homeservice.lsim.timeout', 20))
            ->asJson()
            ->post("{$base}/v1/smsreporter", [
                'login' => $login,
                'transid' => (int) $transactionId,
            ]);

        $json = $response->json();
        // Legacy: bare int / obj / errorCode. Newer LSIM: { message, delivery_status }.
        $status = null;
        $deliveryStatus = null;
        if (is_int($json) || (is_string($json) && is_numeric($json))) {
            $status = (int) $json;
        } elseif (is_array($json)) {
            if (isset($json['delivery_status']) && is_string($json['delivery_status'])) {
                $deliveryStatus = strtoupper(trim($json['delivery_status']));
                $status = match ($deliveryStatus) {
                    'IN_QUEUE', 'QUEUE', 'PENDING' => 100,
                    'DELIVERED', 'DELIVRD' => 101,
                    'UNDELIVERED', 'UNDELIV', 'FAILED' => 102,
                    'EXPIRED' => 103,
                    'REJECTED', 'REJECTD' => 104,
                    'CANCELLED', 'DELETED' => 105,
                    'ERROR' => 106,
                    'UNKNOWN' => 107,
                    'SENT' => 108,
                    'BLACKLIST', 'BLACK_LIST' => 109,
                    default => null,
                };
            } elseif (isset($json['obj']) && is_numeric($json['obj'])) {
                $status = (int) $json['obj'];
            } elseif (isset($json['errorCode']) && is_numeric($json['errorCode'])) {
                $status = (int) $json['errorCode'];
            }
        }

        $labels = [
            100 => 'In queue',
            101 => 'Delivered',
            102 => 'Undelivered',
            103 => 'Expired',
            104 => 'Rejected',
            105 => 'Cancelled',
            106 => 'Error',
            107 => 'Unknown (contact LSIM)',
            108 => 'Sent (to operator, not confirmed on handset)',
            109 => 'Black list',
            -105 => 'Number in black list',
            -106 => 'Invalid transaction id',
            -107 => 'IP not allowed',
            -110 => 'Reporting limit exceed',
        ];

        $label = $deliveryStatus !== null
            ? ($labels[$status] ?? $deliveryStatus)
            : ($status !== null ? ($labels[$status] ?? 'code '.$status) : 'unknown response');

        return [
            'ok' => ($status !== null && $status >= 100 && $status <= 109)
                || in_array($deliveryStatus, ['SENT', 'DELIVERED', 'IN_QUEUE'], true),
            'status' => $status,
            'delivery_status' => $deliveryStatus,
            'label' => $label,
            'raw' => $json,
        ];
    }

    /**
     * @return array{ok: bool, balance: int|null, error_code: int|null, raw: array}
     */
    public function balance(): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('LSIM SMS is not configured.');
        }

        $login = (string) config('homeservice.lsim.login');
        $password = (string) config('homeservice.lsim.password');
        $base = rtrim((string) config('homeservice.lsim.base_url', 'https://apps.lsim.az/quicksms'), '/');
        $key = md5(md5($password).$login);

        $response = Http::acceptJson()
            ->timeout((int) config('homeservice.lsim.timeout', 20))
            ->get("{$base}/v1/balance", [
                'login' => $login,
                'key' => $key,
            ]);

        $json = $response->json();
        if (! is_array($json)) {
            throw new RuntimeException('LSIM balance: invalid response');
        }

        $errorCode = isset($json['errorCode']) ? (int) $json['errorCode'] : 0;

        return [
            'ok' => $errorCode === 0,
            'balance' => isset($json['obj']) ? (int) $json['obj'] : null,
            'error_code' => $errorCode,
            'raw' => $json,
        ];
    }

    public function normalizeMsisdn(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?: '';
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }
        // Local 050… → 99450…
        if (str_starts_with($digits, '0') && strlen($digits) === 10) {
            $digits = '994'.substr($digits, 1);
        }

        return $digits;
    }

    private function sign(string $password, string $login, string $text, string $msisdn, string $sender): string
    {
        return md5(md5($password).$login.$text.$msisdn.$sender);
    }

    private function userMessage(int $errorCode, ?string $providerMessage): string
    {
        return match ($errorCode) {
            -102, -105 => 'Telefon nömrəsi SMS üçün uyğun deyil.',
            -103 => 'LSIM_SENDER yanlışdır və ya hələ aktiv deyil (invalid sender name). LSIM-in verdiyi dəqiq adı yaz.',
            -104 => 'SMS balansı bitib. Bir az sonra yenidən cəhd edin.',
            -100, -108 => 'LSIM key/login/password uyğunsuzdur.',
            -107 => 'SMS serveri icazə vermir (IP).',
            default => $providerMessage
                ? 'SMS göndərilmədi: '.$providerMessage
                : 'SMS göndərilmədi. Bir az sonra yenidən cəhd edin.',
        };
    }
}
