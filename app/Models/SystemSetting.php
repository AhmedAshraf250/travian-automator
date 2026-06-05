<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/**
 * Stores simple global system switches used by the automation dashboard.
 */
#[Fillable(['key', 'value'])]
class SystemSetting extends Model
{
    /**
     * Shared key for the global automation on/off switch.
     */
    public const AUTOMATION_ENABLED_KEY = 'automation_enabled';

    /**
     * Shared key for the global default user agent fallback.
     */
    public const DEFAULT_USER_AGENT_KEY = 'default_user_agent';

    /**
     * Shared key for construction engine defaults.
     */
    public const CONSTRUCTION_DEFAULTS_KEY = 'construction_defaults';

    /**
     * Shared key for default hero automation behavior.
     */
    public const HERO_DEFAULTS_KEY = 'hero_defaults';

    /**
     * Return the default per-resource field upgrade order.
     *
     * @return array{wood: int, clay: int, iron: int, crop: int}
     */
    public static function defaultFieldPriority(): array
    {
        return [
            'wood' => 1,
            'clay' => 2,
            'iron' => 3,
            'crop' => 4,
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
            'value' => 'array',
        ];
    }

    /**
     * Determine whether global automation is enabled.
     */
    public static function automationEnabled(): bool
    {
        if (! static::settingsTableExists()) {
            return true;
        }

        $setting = static::query()->firstWhere('key', static::AUTOMATION_ENABLED_KEY);

        return (bool) ($setting?->value['enabled'] ?? true);
    }

    /**
     * Persist the global automation state.
     */
    public static function setAutomationEnabled(bool $enabled): void
    {
        if (! static::settingsTableExists()) {
            return;
        }

        static::query()->updateOrCreate(
            ['key' => static::AUTOMATION_ENABLED_KEY],
            ['value' => ['enabled' => $enabled]],
        );
    }

    /**
     * Resolve the global default user agent fallback.
     */
    public static function defaultUserAgent(): ?string
    {
        if (! static::settingsTableExists()) {
            return null;
        }

        $setting = static::query()->firstWhere('key', static::DEFAULT_USER_AGENT_KEY);
        $userAgent = $setting?->value['value'] ?? null;

        if (! is_string($userAgent)) {
            return null;
        }

        $normalizedUserAgent = trim($userAgent);

        return $normalizedUserAgent !== '' ? $normalizedUserAgent : null;
    }

    /**
     * Persist the global default user agent fallback.
     */
    public static function setDefaultUserAgent(?string $userAgent): void
    {
        if (! static::settingsTableExists()) {
            return;
        }

        $normalizedUserAgent = is_string($userAgent) ? trim($userAgent) : '';

        static::query()->updateOrCreate(
            ['key' => static::DEFAULT_USER_AGENT_KEY],
            ['value' => ['value' => $normalizedUserAgent]],
        );
    }

    /**
     * Resolve the global construction defaults.
     *
     * @return array{field_priority: array{wood: int, clay: int, iron: int, crop: int}, prioritize_crop_fields_when_negative: bool}
     */
    public static function constructionDefaults(): array
    {
        if (! static::settingsTableExists()) {
            return [
                'field_priority' => static::defaultFieldPriority(),
                'prioritize_crop_fields_when_negative' => true,
            ];
        }

        $setting = static::query()->firstWhere('key', static::CONSTRUCTION_DEFAULTS_KEY);
        $value = is_array($setting?->value) ? $setting->value : [];

        return [
            'field_priority' => static::normalizeFieldPriority($value['field_priority'] ?? null),
            'prioritize_crop_fields_when_negative' => (bool) ($value['prioritize_crop_fields_when_negative'] ?? true),
        ];
    }

    /**
     * Resolve the global hero automation defaults.
     *
     * @return array{
     *     adventures_enabled: bool,
     *     min_health: int,
     *     revive_enabled: bool,
     *     attribute_upgrade_enabled: bool,
     *     attribute_weights: array{power: int, offBonus: int, defBonus: int, productionPoints: int}
     * }
     */
    public static function heroDefaults(): array
    {
        if (! static::settingsTableExists()) {
            return static::defaultHeroDefaults();
        }

        $setting = static::query()->firstWhere('key', static::HERO_DEFAULTS_KEY);
        $value = is_array($setting?->value) ? $setting->value : [];

        return static::normalizeHeroDefaults($value);
    }

    /**
     * Persist the global hero automation defaults.
     *
     * @param  array<string, mixed>  $value
     */
    public static function setHeroDefaults(array $value): void
    {
        if (! static::settingsTableExists()) {
            return;
        }

        static::query()->updateOrCreate(
            ['key' => static::HERO_DEFAULTS_KEY],
            ['value' => static::normalizeHeroDefaults($value)],
        );
    }

    /**
     * Persist the global construction defaults.
     *
     * @param  array<string, mixed>  $value
     */
    public static function setConstructionDefaults(array $value): void
    {
        if (! static::settingsTableExists()) {
            return;
        }

        static::query()->updateOrCreate(
            ['key' => static::CONSTRUCTION_DEFAULTS_KEY],
            ['value' => [
                'field_priority' => static::normalizeFieldPriority($value['field_priority'] ?? $value),
                'prioritize_crop_fields_when_negative' => (bool) ($value['prioritize_crop_fields_when_negative'] ?? true),
            ]],
        );
    }

    /**
     * Build a small settings snapshot for the dashboard.
     *
     * @return array{
     *     automation_enabled: bool,
     *     default_user_agent: ?string,
     *     construction_defaults: array{field_priority: array{wood: int, clay: int, iron: int, crop: int}, prioritize_crop_fields_when_negative: bool},
     *     hero_defaults: array{
     *         adventures_enabled: bool,
     *         min_health: int,
     *         revive_enabled: bool,
     *         attribute_upgrade_enabled: bool,
     *         attribute_weights: array{power: int, offBonus: int, defBonus: int, productionPoints: int}
     *     }
     * }
     */
    public static function dashboardSnapshot(): array
    {
        return [
            'automation_enabled' => static::automationEnabled(),
            'default_user_agent' => static::defaultUserAgent(),
            'construction_defaults' => static::constructionDefaults(),
            'hero_defaults' => static::heroDefaults(),
        ];
    }

    /**
     * Determine whether the backing table is available.
     */
    protected static function settingsTableExists(): bool
    {
        return Schema::hasTable('system_settings');
    }

    /**
     * Normalize field priority input to the four known Travian resources.
     *
     * @return array{wood: int, clay: int, iron: int, crop: int}
     */
    protected static function normalizeFieldPriority(mixed $fieldPriority): array
    {
        $defaults = static::defaultFieldPriority();

        if (! is_array($fieldPriority)) {
            return $defaults;
        }

        return [
            'wood' => (int) ($fieldPriority['wood'] ?? $defaults['wood']),
            'clay' => (int) ($fieldPriority['clay'] ?? $defaults['clay']),
            'iron' => (int) ($fieldPriority['iron'] ?? $defaults['iron']),
            'crop' => (int) ($fieldPriority['crop'] ?? $defaults['crop']),
        ];
    }

    /**
     * Return safe global hero defaults.
     *
     * @return array{
     *     adventures_enabled: bool,
     *     min_health: int,
     *     revive_enabled: bool,
     *     attribute_upgrade_enabled: bool,
     *     attribute_weights: array{power: int, offBonus: int, defBonus: int, productionPoints: int}
     * }
     */
    protected static function defaultHeroDefaults(): array
    {
        return [
            'adventures_enabled' => false,
            'min_health' => 40,
            'revive_enabled' => false,
            'attribute_upgrade_enabled' => false,
            'attribute_weights' => AccountSetting::defaultHeroAttributeWeights(),
        ];
    }

    /**
     * Normalize global hero defaults to the fields supported by v1 automation.
     *
     * @param  array<string, mixed>|null  $value
     * @return array{
     *     adventures_enabled: bool,
     *     min_health: int,
     *     revive_enabled: bool,
     *     attribute_upgrade_enabled: bool,
     *     attribute_weights: array{power: int, offBonus: int, defBonus: int, productionPoints: int}
     * }
     */
    protected static function normalizeHeroDefaults(?array $value): array
    {
        $defaults = static::defaultHeroDefaults();
        $weights = is_array($value['attribute_weights'] ?? null)
            ? $value['attribute_weights']
            : $defaults['attribute_weights'];

        return [
            'adventures_enabled' => (bool) ($value['adventures_enabled'] ?? $defaults['adventures_enabled']),
            'min_health' => max(0, min(100, (int) ($value['min_health'] ?? $defaults['min_health']))),
            'revive_enabled' => (bool) ($value['revive_enabled'] ?? $defaults['revive_enabled']),
            'attribute_upgrade_enabled' => (bool) ($value['attribute_upgrade_enabled'] ?? $defaults['attribute_upgrade_enabled']),
            'attribute_weights' => [
                'power' => max(0, (int) ($weights['power'] ?? 0)),
                'offBonus' => max(0, (int) ($weights['offBonus'] ?? 0)),
                'defBonus' => max(0, (int) ($weights['defBonus'] ?? 0)),
                'productionPoints' => max(0, (int) ($weights['productionPoints'] ?? 0)),
            ],
        ];
    }
}
