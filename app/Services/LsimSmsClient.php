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
        $rawCode = $json['errorCode'] ?? 0;
        $errorCode = $rawCode === null || $rawCode === '' ? 0 : (int) $rawCode;
        $transId = isset($json['obj']) && $json['obj'] !== null && $json['obj'] !== ''
            ? (int) $json['obj']
            : null;
        $ok = $errorCode === 0 && empty($json['errorMessage']) && $transId !== null;

        if (! $ok) {
            Log::warning('LSIM SMS failed', [
                'msisdn' => $msisdn,
                'error_code' => $errorCode,
                'error_message' => $json['errorMessage'] ?? null,
            ]);

            throw ValidationException::withMessages([
                'phone' => [$this->userMessage($errorCode, $json['errorMessage'] ?? null)],
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

        // Azerbaijani letters → unicode=true
        $this->send($phone, $text, true);
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
            -103 => 'SMS göndəricisi konfiqurasiya edilməyib.',
            -104 => 'SMS balansı bitib. Bir az sonra yenidən cəhd edin.',
            -107 => 'SMS serveri icazə vermir (IP).',
            default => $providerMessage
                ? 'SMS göndərilmədi: '.$providerMessage
                : 'SMS göndərilmədi. Bir az sonra yenidən cəhd edin.',
        };
    }
}
