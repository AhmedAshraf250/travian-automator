<?php

namespace App\Models;

use App\Enums\TroopQueueStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tracks training queue intentions and sync results for a village.
 */
#[Fillable([
    'village_id',
    'troop_type',
    'quantity',
    'status',
    'finish_at',
])]
class TroopQueue extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TroopQueueStatus::class,
            'finish_at' => 'immutable_datetime',
        ];
    }

    /**
     * Get the village that owns the queue entry.
     */
    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }
}
