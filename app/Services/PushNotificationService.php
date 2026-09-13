<?php

namespace App\Services;

use App\Jobs\NotifyMatchedProvidersJob;
use App\Models\Conversation;
use App\Models\PushDispatch;
use App\Models\PushDispatchRecipient;
use App\Models\RequestMatch;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Notifications\UserInboxNotification;
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

        $dispatch = $this->createDispatch([
            'source' => 'request',
            'type' => $urgent ? 'urgent_job' : 'new_job',
            'title' => $title,
            'body' => $body,
            'payload' => [
                'type' => $urgent ? 'urgent_job' : 'new_job',
                'request_id' => (string) $request->id,
            ],
            'service_request_id' => $request->id,
        ]);

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
            ], $dispatch);

            if ($ok) {
                $this->markNotified($userMatches);
                $sent++;
            }
        }

        $dispatch->refreshCounts();
        if ($dispatch->targeted_count === 0) {
            $dispatch->delete();
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
    public function sendToUser(
        User $user,
        string $title,
        string $body,
        array $data = [],
        ?PushDispatch $dispatch = null,
    ): bool {
        $ownsDispatch = $dispatch === null;
        if ($ownsDispatch) {
            $type = (string) ($data['type'] ?? 'system');
            $dispatch = $this->createDispatch([
                'source' => $this->sourceFromType($type),
                'type' => $type,
                'title' => $title,
                'body' => $body,
                'payload' => $data,
                'service_request_id' => isset($data['request_id']) ? (int) $data['request_id'] : null,
                'conversation_id' => isset($data['conversation_id']) ? (int) $data['conversation_id'] : null,
            ]);
        }

        try {
            $user->notify(new UserInboxNotification($title, $body, $data));
        } catch (\Throwable $e) {
            Log::warning('Inbox notification save failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }

        $tokens = $user->deviceTokens()->get();
        if ($tokens->isEmpty()) {
            Log::info('Push skipped: no device tokens', ['user_id' => $user->id]);
            $this->recordRecipient($dispatch, $user, 'skipped_no_token');
            if ($ownsDispatch) {
                $dispatch->refreshCounts();
            }

            return false;
        }

        if (! $this->fcm->isConfigured()) {
            Log::info('Push skipped: FCM not configured', [
                'user_id' => $user->id,
                'title' => $title,
                'tokens' => $tokens->count(),
            ]);
            $this->recordRecipient($dispatch, $user, 'failed', 'FCM konfiqurasiya olunmayıb');
            if ($ownsDispatch) {
                $dispatch->refreshCounts();
            }

            return false;
        }

        $anyOk = false;
        $lastError = null;
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

            $lastError = $result['error'] ?? 'FCM xətası';

            if ($result['unregistered']) {
                $device->delete();

                continue;
            }

            Log::warning('FCM send failed', [
                'user_id' => $user->id,
                'error' => $result['error'],
            ]);
        }

        $this->recordRecipient(
            $dispatch,
            $user,
            $anyOk ? 'delivered' : 'failed',
            $anyOk ? null : ($lastError ?: 'Göndərilmədi'),
        );

        if ($ownsDispatch) {
            $dispatch->refreshCounts();
        }

        return $anyOk;
    }

    /**
     * Admin broadcast: send the same title/body to many users.
     *
     * @param  iterable<int|User>  $users
     * @param  array<string, string>  $data
     * @return array{targeted: int, delivered: int, skipped_no_token: int, failed: int, dispatch_id: int|null}
     */
    public function broadcast(
        iterable $users,
        string $title,
        string $body,
        array $data = [],
        ?int $adminId = null,
        ?string $audience = null,
    ): array {
        $stats = [
            'targeted' => 0,
            'delivered' => 0,
            'skipped_no_token' => 0,
            'failed' => 0,
            'dispatch_id' => null,
        ];

        if (! config('homeservice.feature_push', true)) {
            return $stats;
        }

        $payload = array_merge(['type' => 'admin'], $data);

        $dispatch = $this->createDispatch([
            'source' => 'admin',
            'type' => (string) ($payload['type'] ?? 'admin'),
            'title' => $title,
            'body' => $body,
            'payload' => $payload,
            'audience' => $audience,
            'admin_id' => $adminId,
        ]);
        $stats['dispatch_id'] = $dispatch->id;

        foreach ($users as $user) {
            if (! $user instanceof User) {
                $user = User::query()->with('deviceTokens')->find((int) $user);
            }
            if (! $user) {
                continue;
            }

            $stats['targeted']++;

            if ($this->sendToUser($user, $title, $body, $payload, $dispatch)) {
                $stats['delivered']++;
            }
        }

        $dispatch->refreshCounts();
        $stats['delivered'] = $dispatch->delivered_count;
        $stats['skipped_no_token'] = $dispatch->skipped_count;
        $stats['failed'] = $dispatch->failed_count;
        $stats['targeted'] = $dispatch->targeted_count;

        return $stats;
    }

    /**
     * @param  array<string, mixed>  $attrs
     */
    private function createDispatch(array $attrs): PushDispatch
    {
        return PushDispatch::query()->create($attrs);
    }

    private function recordRecipient(
        PushDispatch $dispatch,
        User $user,
        string $status,
        ?string $error = null,
    ): void {
        try {
            PushDispatchRecipient::query()->create([
                'push_dispatch_id' => $dispatch->id,
                'user_id' => $user->id,
                'status' => $status,
                'error' => $error,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Push recipient log failed', [
                'dispatch_id' => $dispatch->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function sourceFromType(string $type): string
    {
        return match ($type) {
            'admin' => 'admin',
            'new_job', 'urgent_job' => 'request',
            'chat_connect', 'chat_message' => 'chat',
            'test' => 'test',
            default => 'system',
        };
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
