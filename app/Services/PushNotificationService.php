<?php

namespace App\Services;

use App\Jobs\NotifyMatchedProvidersJob;
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

        $query = RequestMatch::query()
            ->with(['providerProfile.user.deviceTokens'])
            ->where('service_request_id', $request->id);

        if (! $force) {
            $query->where('notified', false);
        }

        $matches = $query->get();
        if ($matches->isEmpty()) {
            return 0;
        }

        $urgent = (bool) $request->is_urgent;
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
