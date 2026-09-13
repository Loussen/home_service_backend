<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class UserInboxNotification extends Notification
{
    use Queueable;

    /**
     * @param  array<string, string>  $data
     */
    public function __construct(
        public string $title,
        public string $body,
        public array $data = [],
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $type = (string) ($this->data['type'] ?? 'admin');

        return [
            'title' => $this->title,
            'body' => $this->body,
            'type' => $type,
            'payload' => $this->data,
        ];
    }
}
