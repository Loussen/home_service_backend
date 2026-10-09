<?php

namespace App\Support;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Dual-role: inbox / badge must follow active role, not the whole user.
 */
class NotificationAudience
{
    public const CLIENT = 'client';

    public const PROVIDER = 'provider';

    public const BOTH = 'both';

    /**
     * Resolve which role a notification belongs to.
     *
     * @param  array<string, string>  $data
     */
    public static function resolve(string $type, ?User $recipient = null, array $data = []): string
    {
        return match ($type) {
            'new_job', 'urgent_job', 'missed_opportunity' => self::PROVIDER,
            'chat_message', 'chat_connect' => self::chatRole($recipient, $data),
            default => self::BOTH,
        };
    }

    /**
     * @param  array<string, string>  $data
     */
    private static function chatRole(?User $recipient, array $data): string
    {
        $conversationId = (int) ($data['conversation_id'] ?? 0);
        if (! $recipient || $conversationId <= 0) {
            return self::BOTH;
        }

        $conversation = Conversation::query()->find($conversationId);
        if (! $conversation) {
            return self::BOTH;
        }

        if ((int) $conversation->provider_id === (int) $recipient->id) {
            return self::PROVIDER;
        }
        if ((int) $conversation->client_id === (int) $recipient->id) {
            return self::CLIENT;
        }

        return self::BOTH;
    }

    public static function activeRole(User $user): string
    {
        return $user->isProvider() ? self::PROVIDER : self::CLIENT;
    }

    /** Filter database notifications for the user's active role (incl. legacy rows). */
    public static function constrainQuery(Builder $query, User $user): Builder
    {
        $role = self::activeRole($user);
        $driver = DB::connection()->getDriverName();
        $convIdSql = $driver === 'sqlite'
            ? "CAST(json_extract(data, '$.payload.conversation_id') AS INTEGER)"
            : "CAST(JSON_UNQUOTE(JSON_EXTRACT(data, '$.payload.conversation_id')) AS UNSIGNED)";

        return $query->where(function (Builder $q) use ($role, $user, $convIdSql) {
            $q->where('data->payload->audience_role', $role)
                ->orWhere('data->payload->audience_role', self::BOTH)
                ->orWhere(function (Builder $legacy) use ($role, $user, $convIdSql) {
                    $legacy->where(function (Builder $missing) {
                        $missing->whereNull('data->payload->audience_role')
                            ->orWhere('data->payload->audience_role', '');
                    });

                    $legacy->where(function (Builder $byType) use ($role, $user, $convIdSql) {
                        // Shared / admin
                        $byType->where(function (Builder $both) {
                            $both->whereIn('data->type', ['admin', 'system', 'test'])
                                ->orWhere(function (Builder $p) {
                                    $p->whereNull('data->type')
                                        ->whereNull('data->payload->type');
                                });
                        });

                        // Provider-only job marketing
                        if ($role === self::PROVIDER) {
                            $byType->orWhereIn('data->type', [
                                'new_job', 'urgent_job', 'missed_opportunity',
                            ])->orWhereIn('data->payload->type', [
                                'new_job', 'urgent_job', 'missed_opportunity',
                            ]);
                        }

                        // Chat: only threads for this role side
                        $byType->orWhere(function (Builder $chat) use ($role, $user, $convIdSql) {
                            $chat->where(function (Builder $t) {
                                $t->whereIn('data->type', ['chat_message', 'chat_connect'])
                                    ->orWhereIn('data->payload->type', ['chat_message', 'chat_connect']);
                            })->whereExists(function ($sub) use ($role, $user, $convIdSql) {
                                $sub->selectRaw('1')
                                    ->from('conversations')
                                    ->whereRaw("conversations.id = {$convIdSql}");
                                if ($role === self::PROVIDER) {
                                    $sub->where('conversations.provider_id', $user->id);
                                } else {
                                    $sub->where('conversations.client_id', $user->id);
                                }
                            });
                        });
                    });
                });
        });
    }

    public static function unreadCount(User $user): int
    {
        return (int) self::constrainQuery(
            $user->unreadNotifications()->getQuery(),
            $user,
        )->count();
    }
}
