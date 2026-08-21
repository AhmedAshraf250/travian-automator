<?php

namespace App\Application\Accounts\Automation;

use App\Enums\AccountStatus;
use App\Enums\ActivityLogStatus;
use App\Enums\ActivityType;
use App\Models\Account;
use App\Models\ActivityLog;

class RecoverStaleSyncingAccounts
{
    public function handle(): int
    {
        $staleBefore = now()->subMinutes(max(1, (int) config('travian.automation.stale_syncing_minutes', 5)));

        $accounts = Account::query()
            ->where('is_active', true)
            ->where('is_archived', false)
            ->where('status', AccountStatus::Syncing)
            ->where('updated_at', '<=', $staleBefore)
            ->get();

        foreach ($accounts as $account) {
            $account->forceFill([
                'status' => AccountStatus::Error,
                'last_error_at' => now(),
                'last_error_message' => 'Background job timed out or stopped before it could finish.',
            ])->save();

            ActivityLog::query()->create([
                'account_id' => $account->id,
                'activity_type' => ActivityType::Sync,
                'status' => ActivityLogStatus::Failed,
                'message' => 'Background job appears stalled; account status recovered from syncing.',
                'executed_at' => now(),
            ]);
        }

        return $accounts->count();
    }
}
