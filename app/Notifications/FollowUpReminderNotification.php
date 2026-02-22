<?php

namespace App\Notifications;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class FollowUpReminderNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Lead $lead)
    {
    }

    public function via(mixed $notifiable): array
    {
        return ['database'];
    }

    public function toArray(mixed $notifiable): array
    {
        return [
            'title' => 'Follow-up reminder',
            'message' => "Follow-up due for {$this->lead->name}.",
            'lead_id' => $this->lead->id,
            'lead_name' => $this->lead->name,
            'route' => route('leads.show', $this->lead),
            'type' => 'follow_up_reminder',
            'follow_up_date' => optional($this->lead->follow_up_date)->format('Y-m-d'),
        ];
    }
}
