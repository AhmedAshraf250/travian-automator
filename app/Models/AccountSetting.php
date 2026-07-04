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
    'hero_use_global_settings',
    'hero_adventures_enabled',
    'hero_min_health',
    'hero_revive_enabled',
    'hero_attribute_upgrade_enabled',
    'hero_attribute_weights',
])]
class AccountSetting extends Model
{
    /**
     * Default values applied to newly-created account settings.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'accept_quests' => true,
    ];

    /**
     * Return the default resource priority payload.
     *
     * @return list<int>
     */
    public static function defaultResourcePriorities(): array
    {
        $priorities = config('travian.defaults.account_resource_priorities', '15,11,1,1');

        if (is_string($priorities)) {
            $priorities = explode(',', $priorities);
        }

        $priorities = array_map(
            static fn (mixed $priority): int => max(1, (int) $priority),
            array_slice((array) $priorities, 0, 4),
        );

        return array_pad($priorities, 4, 1);
    }

    /**
     * Return the default attribute weight payload.
     *
     * @return array{power: int, offBonus: int, defBonus: int, productionPoints: int}
     */
    public static function defaultHeroAttributeWeights(): array
    {
        return [
            'power' => 0,
            'offBonus' => 0,
            'defBonus' => 0,
            'productionPoints' => 0,
        ];
    }

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
            'hero_use_global_settings' => 'boolean',
            'hero_adventures_enabled' => 'boolean',
            'hero_min_health' => 'integer',
            'hero_revive_enabled' => 'boolean',
            'hero_attribute_upgrade_enabled' => 'boolean',
            'hero_attribute_weights' => 'array',
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
