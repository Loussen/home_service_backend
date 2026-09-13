<?php

namespace App\Services;

use App\Jobs\NotifyMatchedProvidersJob;
use App\Models\Conversation;
use App\Models\RequestMatch;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class PushNotificationService
{
    public function __construct(private readonly FcmClient $fcm) {}

    public function notifyNewMatches(ServiceRequest $request, bool $force = false): void
    {
        if (config('homeservice.push_sync', true)) {
            $this->sendForRequest($request->id, $force);

            return;
        }

        NotifyMatchedProvidersJob::dispatch($request->id, $force);
    }

    public function sendForRequest(int $serviceRequestId, bool $force = false): int
    {
        $request = ServiceRequest::query()
            ->with('category')
            ->find($serviceRequestId);

        if (! $request) {
            return 0;
        }

        if (! config('homeservice.feature_push', true)) {
            return 0;
        }

        $query = RequestMatch::query()
            ->with(['providerProfile.user.deviceTokens'])
            ->where('service_request_id', $request->id);

        if (! $force) {
            $query->where('notified', false);
        }

        $matches = $query->get();
        $urgent = (bool) $request->is_urgent;
        if ($urgent) {
            $radiusKm = (float) config('homeservice.urgent_radius_km', 5);
            $matches = $matches
                ->filter(fn (RequestMatch $m) => $m->distance_km !== null
                    && (float) $m->distance_km <= $radiusKm + 0.01)
                ->values();
        }

        $client = User::query()->find($request->user_id);
        if ($client) {
            $hiddenIds = app(ModerationService::class)->hiddenUserIdsFor($client)
                ->map(fn ($id) => (int) $id)
                ->all();
            if ($hiddenIds !== []) {
                $skipped = $matches->filter(
                    fn (RequestMatch $m) => in_array((int) ($m->providerProfile?->user_id ?? 0), $hiddenIds, true)
                );
                if ($skipped->isNotEmpty()) {
                    $this->markNotified($skipped);
                }
                $matches = $matches
                    ->reject(
                        fn (RequestMatch $m) => in_array((int) ($m->providerProfile?->user_id ?? 0), $hiddenIds, true)
                    )
                    ->values();
            }
        }

        if ($matches->isEmpty()) {
            return 0;
        }

        $title = $urgent
            ? 'Təcili sorğu'
            : 'Sizə uyğun sorğu';
        $place = trim(implode(' · ', array_filter([
            $request->category?->name_az,
            $request->address,
        ])));
        $body = $place !== ''
            ? $place
            : 'İşlər tabında yeni sorğuya baxın';

        $sent = 0;
        $grouped = $matches->groupBy(fn (RequestMatch $m) => $m->providerProfile?->user_id);

        foreach ($grouped as $userId => $userMatches) {
            if (! $userId || (int) $userId === (int) $request->user_id) {
                $this->markNotified($userMatches);

                continue;
            }

            /** @var RequestMatch $first */
            $first = $userMatches->first();
            $user = $first->providerProfile?->user;
            if (! $user) {
                $this->markNotified($userMatches);

                continue;
            }

            $ok = $this->sendToUser($user, $title, $body, [
                'type' => $urgent ? 'urgent_job' : 'new_job',
                'request_id' => (string) $request->id,
                'match_id' => (string) $first->id,
            ]);

            if ($ok) {
                $this->markNotified($userMatches);
                $sent++;
            }
        }

        return $sent;
    }

    public function notifyConnect(Conversation $conversation, User $actor): void
    {
        if (! config('homeservice.feature_push', true)) {
            return;
        }

        $recipientId = (int) $actor->id === (int) $conversation->client_id
            ? (int) $conversation->provider_id
            : (int) $conversation->client_id;

        $recipient = User::query()->find($recipientId);
        if (! $recipient) {
            return;
        }

        if (app(ModerationService::class)->isBlockedEitherWay($actor, $recipient)) {
            return;
        }

        $name = trim((string) ($actor->name ?: 'İstifadəçi'));
        $isClientActing = (int) $actor->id === (int) $conversation->client_id;

        $title = $isClientActing ? 'Yeni CONNECT' : 'Yeni cavab';
        $body = $isClientActing
            ? "{$name} sizinlə əlaqə qurdu"
            : "{$name} sorğunuza cavab verdi";

        $this->sendToUser($recipient, $title, $body, [
            'type' => 'chat_connect',
            'conversation_id' => (string) $conversation->id,
        ]);
    }

    public function notifyNewMessage(Conversation $conversation, User $sender, string $body): void
    {
        if (! config('homeservice.feature_push', true)) {
            return;
        }

        $recipientId = (int) $sender->id === (int) $conversation->client_id
            ? (int) $conversation->provider_id
            : (int) $conversation->client_id;

        $recipient = User::query()->find($recipientId);
        if (! $recipient) {
            return;
        }

        if (app(ModerationService::class)->isBlockedEitherWay($sender, $recipient)) {
            return;
        }

        $name = trim((string) ($sender->name ?: 'İstifadəçi'));
        $preview = trim(preg_replace('/\s+/', ' ', $body) ?? '');
        if (mb_strlen($preview) > 120) {
            $preview = mb_substr($preview, 0, 117).'…';
        }
        if ($preview === '') {
            $preview = 'Yeni mesaj';
        }

        $this->sendToUser($recipient, $name, $preview, [
            'type' => 'chat_message',
            'conversation_id' => (string) $conversation->id,
        ]);
    }

    /**
     * @param  array<string, string>  $data
     */
    public function sendToUser(User $user, string $title, string $body, array $data = []): bool
    {
        $tokens = $user->deviceTokens()->get();
        if ($tokens->isEmpty()) {
            Log::info('Push skipped: no device tokens', ['user_id' => $user->id]);

            return false;
        }

        if (! $this->fcm->isConfigured()) {
            Log::info('Push skipped: FCM not configured', [
                'user_id' => $user->id,
                'title' => $title,
                'tokens' => $tokens->count(),
            ]);

            return false;
        }

        $anyOk = false;
        foreach ($tokens as $device) {
            $result = $this->fcm->send(
                $device->token,
                ['title' => $title, 'body' => $body],
                $data
            );

            if ($result['ok']) {
                $anyOk = true;

                continue;
            }

            if ($result['unregistered']) {
                $device->delete();

                continue;
            }

            Log::warning('FCM send failed', [
                'user_id' => $user->id,
                'error' => $result['error'],
            ]);
        }

        return $anyOk;
    }

    /**
     * Admin broadcast: send the same title/body to many users.
     *
     * @param  iterable<int|User>  $users
     * @param  array<string, string>  $data
     * @return array{targeted: int, delivered: int, skipped_no_token: int}
     */
    public function broadcast(
        iterable $users,
        string $title,
        string $body,
        array $data = [],
    ): array {
        $stats = [
            'targeted' => 0,
            'delivered' => 0,
            'skipped_no_token' => 0,
        ];

        if (! config('homeservice.feature_push', true)) {
            return $stats;
        }

        $payload = array_merge(['type' => 'admin'], $data);

        foreach ($users as $user) {
            if (! $user instanceof User) {
                $user = User::query()->with('deviceTokens')->find((int) $user);
            }
            if (! $user) {
                continue;
            }

            $stats['targeted']++;

            if ($user->deviceTokens()->doesntExist()) {
                $stats['skipped_no_token']++;

                continue;
            }

            if ($this->sendToUser($user, $title, $body, $payload)) {
                $stats['delivered']++;
            }
        }

        return $stats;
    }

    /**
     * @param  iterable<RequestMatch>  $matches
     */
    private function markNotified(iterable $matches): void
    {
        $ids = collect($matches)->pluck('id')->filter()->all();
        if ($ids === []) {
            return;
        }

        RequestMatch::query()->whereIn('id', $ids)->update(['notified' => true]);
    }
}
