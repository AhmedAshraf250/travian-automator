<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Throwable;

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
     * Shared key for default trade automation behavior.
     */
    public const TRADE_DEFAULTS_KEY = 'trade_defaults';

    /**
     * Shared key for local runtime process heartbeats.
     */
    public const RUNTIME_HEARTBEATS_KEY = 'runtime_heartbeats';

    /**
     * Return the default per-resource field upgrade order.
     *
     * @return array{wood: int, clay: int, iron: int, crop: int}
     */
    public static function defaultFieldPriority(): array
    {
        return [
            'wood' => 1,
            'clay' => 1,
            'iron' => 2,
            'crop' => 2,
        ];
    }

    public static function defaultFieldLevelCap(): int
    {
        return 10;
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
     * Mark one local runtime component as alive.
     */
    public static function markRuntimeHeartbeat(string $component, ?CarbonInterface $seenAt = null): void
    {
        if (! static::settingsTableExists()) {
            return;
        }

        $normalizedComponent = static::normalizeRuntimeComponent($component);

        if ($normalizedComponent === null) {
            return;
        }

        $setting = static::query()->firstOrNew(['key' => static::RUNTIME_HEARTBEATS_KEY]);
        $value = is_array($setting->value) ? $setting->value : [];
        $value[$normalizedComponent] = [
            'seen_at' => ($seenAt ?? now())->toIso8601String(),
        ];

        $setting->value = $value;
        $setting->save();
    }

    /**
     * Resolve live/offline runtime process health for the dashboard.
     *
     * @return array{
     *     queue_worker: array{label: string, online: bool, last_seen_at: ?CarbonImmutable, stale_after_seconds: int},
     *     scheduler: array{label: string, online: bool, last_seen_at: ?CarbonImmutable, stale_after_seconds: int},
     *     all_required_online: bool
     * }
     */
    public static function runtimeHealth(?int $staleAfterSeconds = null): array
    {
        if (! static::settingsTableExists()) {
            return static::buildRuntimeHealth([], $staleAfterSeconds);
        }

        $setting = static::query()->firstWhere('key', static::RUNTIME_HEARTBEATS_KEY);

        return static::runtimeHealthFromValue(is_array($setting?->value) ? $setting->value : [], $staleAfterSeconds);
    }

    /**
     * Resolve runtime health from an already loaded system setting value.
     *
     * @param  array<string, mixed>  $value
     * @return array{
     *     queue_worker: array{label: string, online: bool, last_seen_at: ?CarbonImmutable, stale_after_seconds: int},
     *     scheduler: array{label: string, online: bool, last_seen_at: ?CarbonImmutable, stale_after_seconds: int},
     *     all_required_online: bool
     * }
     */
    public static function runtimeHealthFromValue(array $value, ?int $staleAfterSeconds = null): array
    {
        return static::buildRuntimeHealth(static::parseRuntimeHeartbeats($value), $staleAfterSeconds);
    }

    /**
     * @param  array{queue_worker?: CarbonImmutable, scheduler?: CarbonImmutable}  $heartbeats
     * @return array{
     *     queue_worker: array{label: string, online: bool, last_seen_at: ?CarbonImmutable, stale_after_seconds: int},
     *     scheduler: array{label: string, online: bool, last_seen_at: ?CarbonImmutable, stale_after_seconds: int},
     *     all_required_online: bool
     * }
     */
    protected static function buildRuntimeHealth(array $heartbeats, ?int $staleAfterSeconds = null): array
    {
        $staleAfterSeconds = max(15, $staleAfterSeconds ?? (int) config('travian.runtime.heartbeat_stale_seconds', 90));
        $now = now();
        $components = [
            'queue_worker' => 'Queue worker',
            'scheduler' => 'Scheduler',
        ];
        $health = [];

        foreach ($components as $component => $label) {
            $lastSeenAt = $heartbeats[$component] ?? null;

            $health[$component] = [
                'label' => $label,
                'online' => $lastSeenAt instanceof CarbonInterface
                    && $lastSeenAt->diffInSeconds($now, true) <= $staleAfterSeconds,
                'last_seen_at' => $lastSeenAt,
                'stale_after_seconds' => $staleAfterSeconds,
            ];
        }

        $health['all_required_online'] = $health['queue_worker']['online'] && $health['scheduler']['online'];

        return $health;
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
     * @return array{field_priority: array{wood: int, clay: int, iron: int, crop: int}, prioritize_crop_fields_when_negative: bool, field_level_cap: int}
     */
    public static function constructionDefaults(): array
    {
        if (! static::settingsTableExists()) {
            return [
                'field_priority' => static::defaultFieldPriority(),
                'prioritize_crop_fields_when_negative' => true,
                'field_level_cap' => static::defaultFieldLevelCap(),
            ];
        }

        $setting = static::query()->firstWhere('key', static::CONSTRUCTION_DEFAULTS_KEY);
        $value = is_array($setting?->value) ? $setting->value : [];

        return [
            'field_priority' => static::normalizeFieldPriority($value['field_priority'] ?? null),
            'prioritize_crop_fields_when_negative' => (bool) ($value['prioritize_crop_fields_when_negative'] ?? true),
            'field_level_cap' => static::normalizeFieldLevelCap($value['field_level_cap'] ?? null),
        ];
    }

    /**
     * Resolve global trade automation defaults.
     *
     * @return array{max_duration_seconds: int}
     */
    public static function tradeDefaults(): array
    {
        if (! static::settingsTableExists()) {
            return static::defaultTradeDefaults();
        }

        $setting = static::query()->firstWhere('key', static::TRADE_DEFAULTS_KEY);

        return static::normalizeTradeDefaults(is_array($setting?->value) ? $setting->value : []);
    }

    /**
     * Persist global trade automation defaults.
     *
     * @param  array<string, mixed>  $value
     */
    public static function setTradeDefaults(array $value): void
    {
        if (! static::settingsTableExists()) {
            return;
        }

        static::query()->updateOrCreate(
            ['key' => static::TRADE_DEFAULTS_KEY],
            ['value' => static::normalizeTradeDefaults($value)],
        );
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
                'field_level_cap' => static::normalizeFieldLevelCap($value['field_level_cap'] ?? null),
            ]],
        );
    }

    /**
     * Build a small settings snapshot for the dashboard.
     *
     * @return array{
     *     automation_enabled: bool,
     *     default_user_agent: ?string,
     *     construction_defaults: array{field_priority: array{wood: int, clay: int, iron: int, crop: int}, prioritize_crop_fields_when_negative: bool, field_level_cap: int},
     *     trade_defaults: array{max_duration_seconds: int},
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
            'trade_defaults' => static::tradeDefaults(),
            'hero_defaults' => static::heroDefaults(),
        ];
    }

    /**
     * Resolve persisted runtime heartbeat timestamps.
     *
     * @return array{queue_worker?: CarbonImmutable, scheduler?: CarbonImmutable}
     */
    protected static function runtimeHeartbeats(): array
    {
        if (! static::settingsTableExists()) {
            return [];
        }

        $setting = static::query()->firstWhere('key', static::RUNTIME_HEARTBEATS_KEY);

        return static::parseRuntimeHeartbeats(is_array($setting?->value) ? $setting->value : []);
    }

    /**
     * @param  array<string, mixed>  $value
     * @return array{queue_worker?: CarbonImmutable, scheduler?: CarbonImmutable}
     */
    protected static function parseRuntimeHeartbeats(array $value): array
    {
        $heartbeats = [];

        foreach (['queue_worker', 'scheduler'] as $component) {
            $seenAt = $value[$component]['seen_at'] ?? null;

            if (! is_string($seenAt) || trim($seenAt) === '') {
                continue;
            }

            try {
                $heartbeats[$component] = CarbonImmutable::parse($seenAt);
            } catch (Throwable) {
            }
        }

        return $heartbeats;
    }

    protected static function normalizeRuntimeComponent(string $component): ?string
    {
        return match ($component) {
            'queue', 'queue_worker' => 'queue_worker',
            'schedule', 'scheduler' => 'scheduler',
            default => null,
        };
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

    protected static function normalizeFieldLevelCap(mixed $fieldLevelCap): int
    {
        return max(1, min(20, (int) ($fieldLevelCap ?? static::defaultFieldLevelCap())));
    }

    /**
     * @return array{max_duration_seconds: int}
     */
    protected static function defaultTradeDefaults(): array
    {
        return [
            'max_duration_seconds' => 5 * 60 * 60,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $value
     * @return array{max_duration_seconds: int}
     */
    protected static function normalizeTradeDefaults(?array $value): array
    {
        $defaults = static::defaultTradeDefaults();

        return [
            'max_duration_seconds' => max(60, min(7 * 24 * 60 * 60, (int) ($value['max_duration_seconds'] ?? $defaults['max_duration_seconds']))),
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
