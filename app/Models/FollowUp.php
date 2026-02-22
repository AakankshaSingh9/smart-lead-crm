<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FollowUp extends Model
{
    use HasFactory;

    public const STATUSES = ['pending', 'completed', 'missed'];

    protected $fillable = [
        'lead_id',
        'user_id',
        'follow_up_date',
        'notes',
        'status',
        'completed_at',
    ];

    protected $casts = [
        'follow_up_date' => 'date',
        'completed_at' => 'datetime',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
