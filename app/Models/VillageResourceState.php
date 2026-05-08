<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Stores the latest actual or simulated resource values for a village.
 */
#[Fillable([
    'village_id',
    'wood',
    'clay',
    'iron',
    'crop',
    'wood_production',
    'clay_production',
    'iron_production',
    'crop_production',
    'warehouse_capacity',
    'granary_capacity',
    'simulated_at',
    'server_reported_at',
])]
class VillageResourceState extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'simulated_at' => 'immutable_datetime',
            'server_reported_at' => 'immutable_datetime',
        ];
    }

    /**
     * Get the village that owns the state row.
     */
    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }
}
