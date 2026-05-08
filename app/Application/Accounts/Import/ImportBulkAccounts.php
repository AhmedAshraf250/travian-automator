<?php

namespace App\Application\Accounts\Import;

use App\Enums\AccountStatus;
use App\Enums\ActivityLogStatus;
use App\Enums\ActivityType;
use App\Models\Account;
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
     * @return array{imported:int, updated:int}
     */
    public function handle(string $contents): array
    {
        $parser = app(BulkAccountImportParser::class);
        $records = $parser->parse($contents);
        $importedCount = 0;
        $updatedCount = 0;

        DB::transaction(function () use ($records, &$importedCount, &$updatedCount): void {
            foreach ($records as $record) {
                $account = Account::query()->firstOrNew([
                    'server_url' => $record->serverUrl,
                    'username' => $record->username,
                ]);

                $wasExisting = $account->exists;

                $account->fill([
                    'password' => $record->password,
                    'proxy_ip' => $record->proxyIp,
                    'proxy_port' => $record->proxyPort,
                    'user_agent' => $record->userAgent,
                    'is_active' => true,
                    'status' => $account->status ?? AccountStatus::Paused,
                ]);

                $account->save();

                $account->settings()->updateOrCreate(
                    [],
                    [
                        'resource_priorities' => [15, 11, 1, 1],
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
        });

        return [
            'imported' => $importedCount,
            'updated' => $updatedCount,
        ];
    }
}
