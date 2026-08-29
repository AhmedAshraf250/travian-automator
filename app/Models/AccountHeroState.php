<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Stores the latest account-level hero snapshot parsed from Travian pages.
 */
#[Fillable([
    'account_id',
    'status',
    'health_percent',
    'experience_percent',
    'level',
    'adventures_available_count',
    'has_unspent_attribute_points',
    'unspent_attribute_points',
    'hero_remaining_seconds',
    'home_village_travian_id',
    'payload',
    'seen_at',
])]
class AccountHeroState extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'health_percent' => 'float',
            'experience_percent' => 'integer',
            'level' => 'integer',
            'adventures_available_count' => 'integer',
            'has_unspent_attribute_points' => 'boolean',
            'unspent_attribute_points' => 'integer',
            'hero_remaining_seconds' => 'integer',
            'payload' => 'array',
            'seen_at' => 'immutable_datetime',
        ];
    }

    /**
     * Get the account that owns this hero state.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
