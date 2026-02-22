<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\User;

class LeadActivityService
{
    public function log(Lead $lead, ?User $user, string $type, string $description, ?array $meta = null): void
    {
        $lead->activities()->create([
            'user_id' => $user?->id,
            'type' => $type,
            'description' => $description,
            'meta' => $meta,
        ]);
    }
}
