<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Stores automation defaults shared by all villages under a single account.
 */
#[Fillable([
    'account_id',
    'update_period_minutes',
    'min_trade_percent',
    'warehouse_reserve_hours',
    'max_trading_distance',
    'crop_factor_percent',
    'avoid_overflow',
    'random_refresh_enabled',
    'login_period_minutes',
    'logout_period_minutes',
    'time_variability_percent',
    'resource_priorities',
    'negative_crop_priority',
    'read_reports',
    'read_messages',
    'refresh_after_build',
    'refresh_after_attack',
    'accept_quests',
    'generate_user_agent',
])]
class AccountSetting extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'avoid_overflow' => 'boolean',
            'random_refresh_enabled' => 'boolean',
            'resource_priorities' => 'array',
            'read_reports' => 'boolean',
            'read_messages' => 'boolean',
            'refresh_after_build' => 'boolean',
            'refresh_after_attack' => 'boolean',
            'accept_quests' => 'boolean',
            'generate_user_agent' => 'boolean',
        ];
    }

    /**
     * Get the account that owns the settings.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
