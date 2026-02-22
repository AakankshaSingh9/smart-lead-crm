<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Opportunity extends Model
{
    use HasFactory;

    public const STAGES = [
        'prospecting',
        'proposal',
        'negotiation',
        'closed_won',
        'closed_lost',
    ];

    protected $fillable = [
        'name',
        'lead_id',
        'assigned_user_id',
        'estimated_value',
        'probability',
        'expected_close_date',
        'stage',
    ];

    protected $casts = [
        'estimated_value' => 'decimal:2',
        'expected_close_date' => 'date',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }
}
