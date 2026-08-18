<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\FcmClient;
use App\Services\PushNotificationService;
use Illuminate\Console\Command;

class SendTestPushCommand extends Command
{
    protected $signature = 'push:test {userId : User id}';

    protected $description = 'Send a test FCM push to a user\'s registered devices';

    public function handle(PushNotificationService $push, FcmClient $fcm): int
    {
        $user = User::query()->with('deviceTokens')->find($this->argument('userId'));
        if (! $user) {
            $this->error('User not found');

            return self::FAILURE;
        }

        if (! $fcm->isConfigured()) {
            $this->warn('FCM not configured. Set FCM_CREDENTIALS or FCM_PROJECT_ID / FCM_CLIENT_EMAIL / FCM_PRIVATE_KEY.');
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
