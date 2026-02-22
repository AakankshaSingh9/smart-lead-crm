<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Lead extends Model
{
    use HasFactory;

    public const STATUSES = ['new', 'contacted', 'qualified', 'interested', 'converted', 'lost'];

    public const SCORE_BANDS = ['cold', 'warm', 'hot'];

    protected $fillable = [
        'name',
        'email',
        'phone',
        'source',
        'status',
        'assigned_user_id',
        'notes',
        'follow_up_date',
        'score',
        'score_band',
        'conversion_probability',
        'best_follow_up_at',
    ];

    protected $casts = [
        'follow_up_date' => 'date',
        'best_follow_up_at' => 'datetime',
    ];

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function followUps(): HasMany
    {
        return $this->hasMany(FollowUp::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(LeadActivity::class);
    }

    public function opportunity(): HasOne
    {
        return $this->hasOne(Opportunity::class);
    }
}
