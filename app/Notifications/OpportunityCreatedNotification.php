<?php

namespace App\Notifications;

use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OpportunityCreatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Opportunity $opportunity,
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
            'title' => 'Opportunity created',
            'message' => "Opportunity {$this->opportunity->name} was created.",
            'opportunity_id' => $this->opportunity->id,
            'lead_id' => $this->opportunity->lead_id,
            'route' => route('opportunities.index'),
            'type' => 'opportunity_created',
            'actor' => $this->actor?->name,
        ];
    }
}
