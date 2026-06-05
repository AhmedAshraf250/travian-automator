<?php

namespace App\Models;

use App\Enums\VillageCelebrationType;
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
    'prioritize_crop_fields_when_negative',
    'support_enabled',
    'send_enabled',
    'send_min_resource_percentage',
    'send_reserve_resource_percentage',
    'troop_training_enabled',
    'celebration_enabled',
    'celebration_type',
    'celebration_min_culture_points',
])]
class VillageSetting extends Model
{
    /**
     * Return the default per-resource field upgrade order.
     *
     * @return array{wood: int, clay: int, iron: int, crop: int}
     */
    public static function defaultFieldPriority(): array
    {
        return SystemSetting::defaultFieldPriority();
    }

    /**
     * Return the default celebration selection mode.
     */
    public static function defaultCelebrationType(): VillageCelebrationType
    {
        return VillageCelebrationType::Auto;
    }

    /**
     * Return the default minimum celebration culture-points threshold.
     */
    public static function defaultCelebrationMinCulturePoints(): int
    {
        return 200;
    }

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
            'prioritize_crop_fields_when_negative' => 'boolean',
            'support_enabled' => 'boolean',
            'send_enabled' => 'boolean',
            'send_min_resource_percentage' => 'integer',
            'send_reserve_resource_percentage' => 'integer',
            'troop_training_enabled' => 'boolean',
            'celebration_enabled' => 'boolean',
            'celebration_type' => VillageCelebrationType::class,
            'celebration_min_culture_points' => 'integer',
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
