<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Represents a Travian village discovered under a specific account.
 */
#[Fillable([
    'account_id',
    'travian_village_id',
    'name',
    'x',
    'y',
    'population',
    'is_capital',
    'is_active',
    'last_sync_at',
])]
class Village extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_capital' => 'boolean',
            'is_active' => 'boolean',
            'last_sync_at' => 'immutable_datetime',
        ];
    }

    /**
     * Get the account that owns the village.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the settings assigned to the village.
     */
    public function settings(): HasOne
    {
        return $this->hasOne(VillageSetting::class);
    }

    /**
     * Get the current layout targets configured for the village.
     */
    public function buildingTargets(): HasMany
    {
        return $this->hasMany(VillageBuildingTarget::class);
    }

    /**
     * Get the current known buildings of the village.
     */
    public function buildings(): HasMany
    {
        return $this->hasMany(VillageBuilding::class);
    }

    /**
     * Get the current simulated resource state of the village.
     */
    public function resourceState(): HasOne
    {
        return $this->hasOne(VillageResourceState::class);
    }

    /**
     * Get the latest synced runtime snapshot for troops and movements.
     */
    public function runtimeState(): HasOne
    {
        return $this->hasOne(VillageRuntimeState::class);
    }
}
