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
     * Build a small settings snapshot for the dashboard.
     *
     * @return array{automation_enabled: bool, default_user_agent: ?string}
     */
    public static function dashboardSnapshot(): array
    {
        return [
            'automation_enabled' => static::automationEnabled(),
            'default_user_agent' => static::defaultUserAgent(),
        ];
    }

    /**
     * Determine whether the backing table is available.
     */
    protected static function settingsTableExists(): bool
    {
        return Schema::hasTable('system_settings');
    }
}
