<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AppNotificationResource;
use App\Support\NotificationAudience;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $status = $request->query('status', 'all');
        $query = NotificationAudience::constrainQuery(
            $user->notifications()->getQuery(),
            $user,
        )->latest();

        if ($status === 'unread') {
            $query->whereNull('read_at');
        } elseif ($status === 'read') {
            $query->whereNotNull('read_at');
        }

        $page = $query->paginate(40);

        return $this->success([
            'items' => AppNotificationResource::collection($page->getCollection())->resolve(),
            'unread_count' => NotificationAudience::unreadCount($user),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ], 'Notifications');
    }

    public function markRead(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $notification = NotificationAudience::constrainQuery(
            $user->notifications()->getQuery(),
            $user,
        )->where('id', $id)->first();
        abort_if(! $notification, 404, 'Notification not found');

        if ($notification->read_at === null) {
            $notification->markAsRead();
        }

        return $this->success(
            new AppNotificationResource($notification->fresh()),
            'Oxundu'
        );
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $user = $request->user();
        $ids = NotificationAudience::constrainQuery(
            $user->unreadNotifications()->getQuery(),
            $user,
        )->pluck('id');

        if ($ids->isNotEmpty()) {
            $user->notifications()
                ->whereIn('id', $ids)
                ->update(['read_at' => now()]);
        }

        return $this->success([
            'unread_count' => 0,
        ], 'Hamısı oxundu');
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return $this->success([
            'unread_count' => NotificationAudience::unreadCount($request->user()),
        ]);
    }
}
