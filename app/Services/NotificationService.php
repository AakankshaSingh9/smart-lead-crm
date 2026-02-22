<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\User;
use App\Notifications\BroadcastNotification;
use App\Notifications\FollowUpReminderNotification;
use App\Notifications\LeadAssignedNotification;
use App\Notifications\LeadConvertedNotification;
use App\Notifications\OpportunityCreatedNotification;
use Illuminate\Support\Facades\Notification;

class NotificationService
{
    public function notifyLeadAssigned(Lead $lead, ?User $assignee, ?User $actor = null): void
    {
        if (! $assignee) {
            return;
        }

        $assignee->notify(new LeadAssignedNotification($lead, $actor));
    }

    public function notifyLeadConverted(Lead $lead, ?User $actor = null): void
    {
        $targets = collect([$lead->assignedUser])->filter();

        if ($targets->isEmpty()) {
            return;
        }

        Notification::send($targets, new LeadConvertedNotification($lead, $actor));
    }

    public function notifyOpportunityCreated(Opportunity $opportunity, ?User $actor = null): void
    {
        $target = $opportunity->assignedUser;

        if (! $target) {
            return;
        }

        $target->notify(new OpportunityCreatedNotification($opportunity, $actor));
    }

    public function notifyFollowUpReminder(Lead $lead, User $user): void
    {
        $existing = $user->unreadNotifications()
            ->where('type', FollowUpReminderNotification::class)
            ->where('data->lead_id', $lead->id)
            ->whereDate('created_at', now()->toDateString())
            ->exists();

        if ($existing) {
            return;
        }

        $user->notify(new FollowUpReminderNotification($lead));
    }

    public function broadcast(string $message, User $actor): void
    {
        $users = User::query()->get();
        Notification::send($users, new BroadcastNotification($message, $actor));
    }
}
