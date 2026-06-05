<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Stores the latest synced runtime snapshot for village troops and movements.
 */
#[Fillable([
    'village_id',
    'tribe_id',
    'troop_slots',
    'incoming_attack_count',
    'incoming_reinforcement_count',
    'outgoing_movement_count',
    'movement_entries',
    'construction_entries',
    'construction_resource_shortages',
    'hero_status',
    'hero_remaining_seconds',
    'server_reported_at',
])]
class VillageRuntimeState extends Model
{
    private const int TROOP_SLOTS_INCLUDING_HERO = 11;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'troop_slots' => 'array',
            'movement_entries' => 'array',
            'construction_entries' => 'array',
            'construction_resource_shortages' => 'array',
            'server_reported_at' => 'immutable_datetime',
        ];
    }

    /**
     * Get the village that owns the runtime snapshot.
     */
    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }

    /**
     * Return the synced troop slots as a normalized integer list.
     *
     * @return list<int>
     */
    public function normalizedTroopSlots(): array
    {
        $slots = is_array($this->troop_slots) ? $this->troop_slots : [];

        $normalizedSlots = array_map(
            static fn (mixed $value): int => (int) $value,
            array_values($slots),
        );

        return array_slice(
            array_pad($normalizedSlots, self::TROOP_SLOTS_INCLUDING_HERO, 0),
            0,
            self::TROOP_SLOTS_INCLUDING_HERO,
        );
    }
}
