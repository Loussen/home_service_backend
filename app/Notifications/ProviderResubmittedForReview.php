<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ProviderResubmittedForReview extends Notification
{
    use Queueable;

    public function __construct(public User $provider) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'İcraçı yenidən baxışa göndərdi',
            'body' => ($this->provider->name ?: $this->provider->phone).' profilini yeniləyib yenidən review gözləyir.',
            'user_id' => $this->provider->id,
            'phone' => $this->provider->phone,
            'url' => '/admin/users/'.$this->provider->id.'/edit',
            'type' => 'provider_resubmit',
        ];
    }
}
