<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AppNotificationResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $status = $request->query('status', 'all');
        $query = $request->user()->notifications()->latest();

        if ($status === 'unread') {
            $query->whereNull('read_at');
        } elseif ($status === 'read') {
            $query->whereNotNull('read_at');
        }

        $page = $query->paginate(40);

        return $this->success([
            'items' => AppNotificationResource::collection($page->getCollection())->resolve(),
            'unread_count' => $request->user()->unreadNotifications()->count(),
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
        $notification = $request->user()->notifications()->where('id', $id)->first();
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
        $request->user()->unreadNotifications->markAsRead();

        return $this->success([
            'unread_count' => 0,
        ], 'Hamısı oxundu');
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return $this->success([
            'unread_count' => $request->user()->unreadNotifications()->count(),
        ]);
    }
}
