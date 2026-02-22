<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\User;

class OpportunityService
{
    public function createFromLead(Lead $lead, ?User $actor = null): Opportunity
    {
        return Opportunity::query()->firstOrCreate(
            ['lead_id' => $lead->id],
            [
                'name' => $lead->name.' Opportunity',
                'assigned_user_id' => $lead->assigned_user_id,
                'estimated_value' => 0,
                'probability' => $lead->conversion_probability,
                'expected_close_date' => now()->addDays(30)->toDateString(),
                'stage' => 'prospecting',
            ]
        );
    }
}
