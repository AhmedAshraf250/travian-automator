<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Stores the latest known real state of a building slot inside a village.
 */
#[Fillable([
    'village_id',
    'slot_id',
    'building_gid',
    'building_type',
    'current_level',
    'is_under_construction',
    'automation_enabled',
    'finish_at',
])]
class VillageBuilding extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'slot_id' => 'integer',
            'building_gid' => 'integer',
            'current_level' => 'integer',
            'is_under_construction' => 'boolean',
            'automation_enabled' => 'boolean',
            'finish_at' => 'immutable_datetime',
        ];
    }

    /**
     * Get the village that owns the building state.
     */
    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }
}
