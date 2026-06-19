<?php

namespace App\Application\Accounts\Connection;

use App\Enums\AccountStatus;
use App\Enums\ActivityLogStatus;
use App\Enums\ActivityType;
use App\Jobs\SyncTravianAccountJob;
use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\SystemSetting;

class DispatchDueConnectionRetries
{
    /**
     * Queue accounts whose connection cooldown has elapsed.
     */
    public function handle(int $limit = 10): int
    {
        if (! SystemSetting::automationEnabled()) {
            return 0;
        }

        $accounts = Account::query()
            ->where('is_active', true)
            ->where('is_archived', false)
            ->where('status', AccountStatus::ConnectionIssue)
            ->whereNotNull('connection_retry_after')
            ->where('connection_retry_after', '<=', now())
            ->orderBy('connection_retry_after')
            ->limit(max(1, $limit))
            ->get();

        foreach ($accounts as $account) {
            $account->forceFill([
                'status' => AccountStatus::Syncing,
            ])->save();

            ActivityLog::query()->create([
                'account_id' => $account->id,
                'activity_type' => ActivityType::Sync,
                'status' => ActivityLogStatus::Pending,
                'message' => 'Connection retry window elapsed; sync queued automatically.',
                'scheduled_at' => now(),
            ]);

            SyncTravianAccountJob::dispatch($account->id, null, true);
        }

        return $accounts->count();
    }
}
