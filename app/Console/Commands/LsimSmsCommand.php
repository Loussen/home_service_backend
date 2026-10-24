<?php

namespace App\Console\Commands;

use App\Services\LsimSmsClient;
use Illuminate\Console\Command;

class LsimSmsCommand extends Command
{
    protected $signature = 'sms:lsim
                            {action=balance : balance|send|report}
                            {--phone= : E.164 or 994… for send}
                            {--text= : Message body for send}
                            {--transid= : Transaction id for report}
                            {--unicode : Force unicode=true on send}';

    protected $description = 'LSIM QuickSMS: balance, send test SMS, or delivery report';

    public function handle(LsimSmsClient $lsim): int
    {
        if (! $lsim->isConfigured()) {
            $this->error('Set LSIM_LOGIN, LSIM_PASSWORD, LSIM_SENDER in .env');

            return self::FAILURE;
        }

        $action = strtolower((string) $this->argument('action'));

        if ($action === 'balance') {
            $result = $lsim->balance();
            if (! $result['ok']) {
                $this->error('Balance failed. errorCode='.($result['error_code'] ?? '?'));
                $this->line(json_encode($result['raw'], JSON_UNESCAPED_UNICODE));

                return self::FAILURE;
            }
            $this->info('LSIM balance: '.$result['balance']);

            return self::SUCCESS;
        }

        if ($action === 'send') {
            $phone = (string) $this->option('phone');
            $text = (string) ($this->option('text') ?: 'My Sancho test SMS');
            if ($phone === '') {
                $this->error('--phone= required (e.g. +99450xxxxxxx)');

                return self::FAILURE;
            }
            $unicode = (bool) $this->option('unicode')
                || (bool) preg_match('/[^\x00-\x7F]/', $text);
            $msisdn = $lsim->normalizeMsisdn($phone);
            $this->line("msisdn={$msisdn} unicode=".($unicode ? 'true' : 'false'));
            $result = $lsim->send($phone, $text, $unicode);
            $this->info('Accepted by LSIM. transaction_id='.$result['transaction_id']);
            $this->comment('Check delivery: php artisan sms:lsim report --transid='.$result['transaction_id']);

            return self::SUCCESS;
        }

        if ($action === 'report') {
            $transId = $this->option('transid');
            if ($transId === null || $transId === '') {
                $this->error('--transid= required (from send / laravel.log)');

                return self::FAILURE;
            }
            $result = $lsim->report($transId);
            $this->info('status='.($result['status'] ?? '?').' → '.$result['label']);
            $this->line(json_encode($result['raw'], JSON_UNESCAPED_UNICODE));

            return $result['ok'] || in_array($result['status'], [100, 101, 108], true)
                ? self::SUCCESS
                : self::FAILURE;
        }

        $this->error('Unknown action. Use: balance | send | report');

        return self::FAILURE;
    }
}
