<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Stores the desired building plan for a village slot.
 */
#[Fillable([
    'village_id',
    'slot_id',
    'building_type',
    'target_level',
    'priority',
    'is_enabled',
])]
class VillageBuildingTarget extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
        ];
    }

    /**
     * Get the village that owns the target.
     */
    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }
}
