<?php

namespace App\Application\Accounts\Import;

use App\Enums\AccountStatus;
use App\Enums\ActivityLogStatus;
use App\Enums\ActivityType;
use App\Models\Account;
use App\Models\AccountProxy;
use App\Models\AccountSetting;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;

/**
 * Creates or updates accounts and their default settings from parsed import data.
 */
class ImportBulkAccounts
{
    /**
     * Create or update accounts from textarea content.
     *
     * @return array{imported:int, updated:int, archived:int, account_ids:list<int>}
     */
    public function handle(string $contents): array
    {
        $parser = app(BulkAccountImportParser::class);
        $records = $parser->parse($contents);
        $importedCount = 0;
        $updatedCount = 0;
        $archivedCount = 0;
        $accountIds = [];

        DB::transaction(function () use ($records, &$importedCount, &$updatedCount, &$archivedCount, &$accountIds): void {
            $activeImportKeys = [];

            foreach ($records as $position => $record) {
                $activeImportKeys[$this->buildImportKey($record->serverUrl, $record->username)] = true;

                $account = Account::query()->firstOrNew([
                    'server_url' => $record->serverUrl,
                    'username' => $record->username,
                ]);

                $wasExisting = $account->exists;

                $account->fill([
                    'password' => $record->password,
                    'proxy_scheme' => $record->proxyScheme,
                    'proxy_ip' => $record->proxyIp,
                    'proxy_port' => $record->proxyPort,
                    'proxy_username' => $record->proxyUsername,
                    'proxy_password' => $record->proxyPassword,
                    'user_agent' => $record->userAgent,
                    'managed_by_import' => true,
                    'is_archived' => false,
                    'import_position' => $position + 1,
                    'is_active' => true,
                    'status' => $account->status ?? AccountStatus::Paused,
                ]);

                $account->save();
                $accountIds[] = (int) $account->id;

                if ($record->proxyIp !== null && $record->proxyPort !== null) {
                    $proxy = $account->proxies()->updateOrCreate(
                        ['position' => 1],
                        [
                            'scheme' => $record->proxyScheme,
                            'host' => $record->proxyIp,
                            'port' => $record->proxyPort,
                            'username' => $record->proxyUsername,
                            'password' => $record->proxyPassword,
                            'status' => AccountProxy::StatusActive,
                            'failure_count' => 0,
                            'last_failed_at' => null,
                            'cooldown_until' => null,
                            'last_error_message' => null,
                        ],
                    );

                    $account->forceFill([
                        'active_account_proxy_id' => $proxy->id,
                    ])->save();
                } else {
                    $account->forceFill([
                        'active_account_proxy_id' => null,
                        'session_cookies' => null,
                        'session_transport_fingerprint' => null,
                    ])->save();
                }

                $account->settings()->updateOrCreate(
                    [],
                    [
                        'resource_priorities' => AccountSetting::defaultResourcePriorities(),
                    ],
                );

                ActivityLog::query()->create([
                    'account_id' => $account->id,
                    'activity_type' => ActivityType::Import,
                    'status' => ActivityLogStatus::Done,
                    'payload' => [
                        'server_url' => $record->serverUrl,
                        'username' => $record->username,
                    ],
                    'message' => $wasExisting
                        ? 'Account import refreshed account credentials and transport settings.'
                        : 'Account imported successfully.',
                    'executed_at' => now(),
                ]);

                if ($wasExisting) {
                    $updatedCount++;
                } else {
                    $importedCount++;
                }
            }

            $managedAccounts = Account::query()
                ->where('managed_by_import', true)
                ->where('is_archived', false)
                ->get();

            foreach ($managedAccounts as $managedAccount) {
                $managedAccountKey = $this->buildImportKey($managedAccount->server_url, $managedAccount->username);

                if (isset($activeImportKeys[$managedAccountKey])) {
                    continue;
                }

                $managedAccount->forceFill([
                    'is_archived' => true,
                    'is_active' => false,
                    'status' => AccountStatus::Paused,
                ])->save();

                ActivityLog::query()->create([
                    'account_id' => $managedAccount->id,
                    'activity_type' => ActivityType::Import,
                    'status' => ActivityLogStatus::Done,
                    'message' => 'Account archived because it was removed from the latest bulk import snapshot.',
                    'executed_at' => now(),
                ]);

                $archivedCount++;
            }
        });

        return [
            'imported' => $importedCount,
            'updated' => $updatedCount,
            'archived' => $archivedCount,
            'account_ids' => array_values(array_unique($accountIds)),
        ];
    }

    /**
     * Build the unique reconciliation key for one import line.
     */
    protected function buildImportKey(string $serverUrl, string $username): string
    {
        return mb_strtolower($serverUrl).'|'.mb_strtolower($username);
    }
}
