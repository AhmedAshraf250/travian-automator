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
    'available_merchants',
    'merchant_capacity',
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

    /**
     * Get the warehouse capacity left before wood, clay, or iron would overflow.
     */
    public function warehouseRemaining(): int
    {
        return max(
            0,
            $this->warehouse_capacity - max($this->wood, $this->clay, $this->iron),
        );
    }

    /**
     * Get the granary capacity left before crop would overflow.
     */
    public function granaryRemaining(): int
    {
        return max(0, $this->granary_capacity - $this->crop);
    }

    /**
     * Get the warehouse usage percentage based on the highest stored basic resource.
     */
    public function warehouseUsagePercentage(): int
    {
        if ($this->warehouse_capacity <= 0) {
            return 0;
        }

        return (int) round((max($this->wood, $this->clay, $this->iron) / $this->warehouse_capacity) * 100);
    }

    /**
     * Get the granary usage percentage based on current crop storage.
     */
    public function granaryUsagePercentage(): int
    {
        if ($this->granary_capacity <= 0) {
            return 0;
        }

        return (int) round(($this->crop / $this->granary_capacity) * 100);
    }
}
