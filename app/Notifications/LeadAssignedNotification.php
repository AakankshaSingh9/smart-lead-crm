<?php

namespace App\Notifications;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LeadAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Lead $lead,
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
            'title' => 'New lead assigned',
            'message' => "Lead {$this->lead->name} was assigned to you.",
            'lead_id' => $this->lead->id,
            'lead_name' => $this->lead->name,
            'route' => route('leads.show', $this->lead),
            'type' => 'lead_assigned',
            'actor' => $this->actor?->name,
        ];
    }
}
