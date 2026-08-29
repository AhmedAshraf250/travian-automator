<?php

namespace App\Livewire\Dashboard\Concerns;

use App\Application\Accounts\Import\BulkAccountImportParser;
use App\Enums\AccountStatus;
use App\Models\Account;
use App\Models\AccountSetting;
use App\Models\ActivityLog;
use App\Models\SystemSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

trait BuildsDashboardViewData
{
    /**
     * Load the dashboard accounts with the relationships needed by the UI.
     *
     * @return Collection<int, Account>
     */
    protected function loadAccounts(): Collection
    {
        $query = Account::query();

        if (Schema::hasColumn('accounts', 'is_archived')) {
            $query->where('is_archived', false);
        }

        if (Schema::hasColumn('accounts', 'import_position')) {
            $query
                ->orderByRaw('case when import_position > 0 then 0 else 1 end')
                ->orderBy('import_position');
        }

        return $query
            ->withCount('villages')
            ->orderBy('id')
            ->get();
    }

    /**
     * Load the most recent activity log rows for the footer timeline.
     *
     * @return Collection<int, ActivityLog>
     */
    protected function loadActivityLogs(): Collection
    {
        return ActivityLog::query()
            ->with(['account', 'village'])
            ->latest()
            ->limit(50)
            ->get();
    }

    /**
     * Count recent activity rows without loading the timeline payload.
     */
    protected function activityLogCount(): int
    {
        return ActivityLog::query()
            ->latest()
            ->limit(50)
            ->pluck('id')
            ->count();
    }

    /**
     * Build a per-line visual preview for Accounts & Login input.
     *
     * @return list<array{line:int, valid:bool, server:string, username:string, password:string, proxy:string, user_agent:string, error:string|null}>
     */
    protected function buildImportPreviewRows(): array
    {
        $rows = [];
        $parser = app(BulkAccountImportParser::class);

        foreach (preg_split('/\R/u', $this->bulkImportDraft) ?: [] as $lineIndex => $line) {
            $trimmedLine = trim($line);

            if ($trimmedLine === '') {
                continue;
            }

            try {
                $record = $parser->parsePreviewLine($trimmedLine, $lineIndex + 1);
                $rows[] = [
                    'line' => $lineIndex + 1,
                    'valid' => true,
                    'server' => $record->serverUrl,
                    'username' => $record->username,
                    'password' => str_repeat('•', max(4, min(10, mb_strlen($record->password)))),
                    'proxy' => $record->proxyIp !== null && $record->proxyPort !== null
                        ? "{$record->proxyScheme}://{$record->proxyIp}:{$record->proxyPort}"
                        : 'Direct',
                    'user_agent' => $record->userAgent ?? 'Default UA',
                    'error' => null,
                ];
            } catch (Throwable $throwable) {
                $rows[] = [
                    'line' => $lineIndex + 1,
                    'valid' => false,
                    'server' => trim($trimmedLine, '|'),
                    'username' => '',
                    'password' => '',
                    'proxy' => '',
                    'user_agent' => '',
                    'error' => $throwable->getMessage(),
                ];
            }
        }

        return $rows;
    }

    /**
     * Build the top-level dashboard statistics.
     *
     * @param  Collection<int, Account>  $accounts
     * @return array<string, int>
     */
    protected function buildStats(Collection $accounts): array
    {
        return [
            'accounts' => $accounts->count(),
            'activeAccounts' => $accounts->where('is_active', true)->count(),
            'villages' => $accounts->sum('villages_count'),
            'syncing' => $accounts->where('status', AccountStatus::Syncing)->count(),
        ];
    }

    /**
     * Build the empty-state payload used before migrations are available.
     *
     * @return array{
     *     accounts: Collection<int, Account>,
     *     activityLogs: Collection<int, ActivityLog>,
     *     stats: array<string, int>
     * }
     */
    protected function emptyDashboardState(): array
    {
        return [
            'accounts' => collect(),
            'activityLogs' => collect(),
            'marketplaceTransferVillages' => collect(),
            'marketplaceTransferCapacity' => [
                'available_merchants' => null,
                'merchant_capacity' => $this->merchantCapacityForTribe(null),
                'total_capacity' => null,
                'resources' => [
                    'wood' => null,
                    'clay' => null,
                    'iron' => null,
                    'crop' => null,
                ],
                'reported_at' => null,
            ],
            'demolitionSnapshot' => [],
            'demolitionBuildings' => collect(),
            'activityLogCount' => 0,
            'stats' => [
                'accounts' => 0,
                'activeAccounts' => 0,
                'villages' => 0,
                'syncing' => 0,
            ],
            ...$this->buildSystemSettingsViewData(),
        ];
    }

    /**
     * Build only the system settings needed by the currently visible dashboard.
     *
     * @return array<string, mixed>
     */
    protected function buildSystemSettingsViewData(): array
    {
        $constructionDefaults = [
            'field_priority' => SystemSetting::defaultFieldPriority(),
            'prioritize_crop_fields_when_negative' => true,
            'field_level_cap' => SystemSetting::defaultFieldLevelCap(),
        ];
        $settingsTableExists = Schema::hasTable('system_settings');
        $baseSettings = $settingsTableExists
            ? SystemSetting::query()
                ->whereIn('key', [SystemSetting::AUTOMATION_ENABLED_KEY, SystemSetting::DEFAULT_USER_AGENT_KEY, SystemSetting::RUNTIME_HEARTBEATS_KEY])
                ->get()
                ->keyBy('key')
            : collect();
        $defaultUserAgent = $baseSettings
            ->get(SystemSetting::DEFAULT_USER_AGENT_KEY)
            ?->value['value'] ?? null;
        $defaultUserAgent = is_string($defaultUserAgent) && trim($defaultUserAgent) !== ''
            ? trim($defaultUserAgent)
            : null;

        $payload = [
            'automationEnabled' => (bool) ($baseSettings->get(SystemSetting::AUTOMATION_ENABLED_KEY)?->value['enabled'] ?? true),
            'runtimeHealth' => SystemSetting::runtimeHealthFromValue(
                is_array($baseSettings->get(SystemSetting::RUNTIME_HEARTBEATS_KEY)?->value ?? null)
                    ? $baseSettings->get(SystemSetting::RUNTIME_HEARTBEATS_KEY)->value
                    : [],
            ),
            'globalDefaultUserAgent' => $defaultUserAgent,
            'globalFieldPriority' => $constructionDefaults['field_priority'],
            'globalFieldLevelCap' => $constructionDefaults['field_level_cap'],
            'globalPrioritizeCropFieldsWhenNegative' => $constructionDefaults['prioritize_crop_fields_when_negative'],
            'globalTradeDefaults' => ['max_duration_seconds' => 5 * 60 * 60],
            'globalHeroDefaults' => [
                'adventures_enabled' => false,
                'min_health' => 40,
                'revive_enabled' => false,
                'attribute_upgrade_enabled' => false,
                'attribute_weights' => AccountSetting::defaultHeroAttributeWeights(),
            ],
        ];

        if ($this->showVillageBuildPlanModal || $this->expandedAccountIds() !== [] || $this->expandedAccounts === []) {
            $constructionDefaults = SystemSetting::constructionDefaults();

            $payload['globalFieldPriority'] = $constructionDefaults['field_priority'];
            $payload['globalFieldLevelCap'] = (int) ($constructionDefaults['field_level_cap'] ?? SystemSetting::defaultFieldLevelCap());
            $payload['globalPrioritizeCropFieldsWhenNegative'] = (bool) $constructionDefaults['prioritize_crop_fields_when_negative'];
        }

        return $payload;
    }

    /**
     * Build a cheap revision fingerprint from local tables that affect the dashboard.
     */
    protected function computeDashboardRevision(): string
    {
        if (! Schema::hasTable('accounts')) {
            return 'empty';
        }

        $tables = [
            'accounts' => 'updated_at',
            'account_proxies' => 'updated_at',
            'account_hero_states' => 'updated_at',
            'villages' => 'updated_at',
            'village_settings' => 'updated_at',
            'village_resource_states' => 'updated_at',
            'village_runtime_states' => 'updated_at',
            'village_buildings' => 'updated_at',
            'village_building_targets' => 'updated_at',
            'village_troop_snapshots' => 'updated_at',
            'village_troop_orders' => 'updated_at',
        ];

        $parts = [];

        foreach ($tables as $table => $column) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }

            $parts[$table] = DB::table($table)->max($column);
        }

        if (Schema::hasTable('activity_logs')) {
            $parts['activity_logs'] = DB::table('activity_logs')->max('id');
        }

        if (Schema::hasTable('system_settings')) {
            $parts['system_settings'] = DB::table('system_settings')->max('updated_at');
        }

        return sha1(json_encode($parts, JSON_THROW_ON_ERROR));
    }
}
