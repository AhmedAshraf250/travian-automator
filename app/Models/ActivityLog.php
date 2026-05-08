<?php

namespace App\Models;

use App\Enums\ActivityLogStatus;
use App\Enums\ActivityType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Stores a user-facing timeline of automation work and manual actions.
 */
#[Fillable([
    'account_id',
    'village_id',
    'activity_type',
    'status',
    'payload',
    'result',
    'message',
    'scheduled_at',
    'executed_at',
])]
class ActivityLog extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'activity_type' => ActivityType::class,
            'status' => ActivityLogStatus::class,
            'payload' => 'array',
            'result' => 'array',
            'scheduled_at' => 'immutable_datetime',
            'executed_at' => 'immutable_datetime',
        ];
    }

    /**
     * Get the account associated with the log row.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the village associated with the log row.
     */
    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }
}
