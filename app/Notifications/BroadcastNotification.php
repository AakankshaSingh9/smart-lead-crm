<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BroadcastNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $message,
        private readonly ?User $actor = null,
    ) {
    }

    public function via(mixed $notifiable): array
    {
        return ['database'];
    }

    public function toArray(mixed $notifiable): array
    {
        return [
            'title' => 'Admin broadcast',
            'message' => $this->message,
            'route' => route('dashboard'),
            'type' => 'broadcast',
            'actor' => $this->actor?->name,
        ];
    }
}
