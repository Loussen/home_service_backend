<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\FcmClient;
use App\Services\PushNotificationService;
use Illuminate\Console\Command;

class SendTestPushCommand extends Command
{
    protected $signature = 'push:test
                            {userId? : User id (omit when using --token)}
                            {--token= : Send directly to this FCM device token}';

    protected $description = 'Send a test FCM push to a user\'s devices or a raw token';

    public function handle(PushNotificationService $push, FcmClient $fcm): int
    {
        if (! $fcm->isConfigured()) {
            $this->error('FCM not configured. Set FCM_CREDENTIALS or FCM_PROJECT_ID / FCM_CLIENT_EMAIL / FCM_PRIVATE_KEY.');

            return self::FAILURE;
        }

        $rawToken = trim((string) $this->option('token'));
        if ($rawToken !== '') {
            $result = $fcm->send(
                $rawToken,
                ['title' => 'Sizə uyğun sorğu', 'body' => 'Test bildirişi — İşlər tabını açın'],
                ['type' => 'new_job']
            );
            if ($result['ok']) {
                $this->info('Push sent to token');

                return self::SUCCESS;
            }
            $this->error('FCM error: '.($result['error'] ?? 'unknown'));

            return self::FAILURE;
        }

        $userId = $this->argument('userId');
        if (! $userId) {
            $this->error('Provide userId or --token=');

            return self::FAILURE;
        }

        $user = User::query()->with('deviceTokens')->find($userId);
        if (! $user) {
            $this->error('User not found');

            return self::FAILURE;
        }

        $this->info('Tokens: '.$user->deviceTokens->count());
        $ok = $push->sendToUser($user, 'Sizə uyğun sorğu', 'Test bildirişi — İşlər tabını açın', [
            'type' => 'new_job',
        ]);

        if ($ok) {
            $this->info('Push sent');

            return self::SUCCESS;
        }

        $this->error('Push was not delivered (no tokens, FCM off, or FCM error — see laravel.log)');

        return self::FAILURE;
    }
}
