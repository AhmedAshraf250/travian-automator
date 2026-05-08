<?php

namespace App\Models;

use App\Enums\VillageTradeMode;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Stores village-level overrides for automation behavior.
 */
#[Fillable([
    'village_id',
    'inherit_from_account',
    'field_priority',
    'pause_buildings',
    'pause_fields',
    'trade_mode',
    'support_enabled',
    'send_enabled',
    'troop_training_enabled',
    'celebration_enabled',
])]
class VillageSetting extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'inherit_from_account' => 'boolean',
            'field_priority' => 'array',
            'pause_buildings' => 'boolean',
            'pause_fields' => 'boolean',
            'trade_mode' => VillageTradeMode::class,
            'support_enabled' => 'boolean',
            'send_enabled' => 'boolean',
            'troop_training_enabled' => 'boolean',
            'celebration_enabled' => 'boolean',
        ];
    }

    /**
     * Get the village that owns the settings.
     */
    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }
}
