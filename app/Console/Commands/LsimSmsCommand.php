<?php

namespace App\Console\Commands;

use App\Services\LsimSmsClient;
use Illuminate\Console\Command;

class LsimSmsCommand extends Command
{
    protected $signature = 'sms:lsim
                            {action=balance : balance|send}
                            {--phone= : E.164 or 994… for send}
                            {--text= : Message body for send}';

    protected $description = 'LSIM QuickSMS: check balance or send a test SMS';

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
            $result = $lsim->send($phone, $text, true);
            $this->info('Sent. transaction_id='.$result['transaction_id']);

            return self::SUCCESS;
        }

        $this->error('Unknown action. Use: balance | send');

        return self::FAILURE;
    }
}
