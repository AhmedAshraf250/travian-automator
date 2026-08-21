<?php

namespace App\Application\Accounts\Automation;

use App\Enums\AccountStatus;
use App\Enums\ActivityLogStatus;
use App\Enums\ActivityType;
use App\Jobs\RunTravianAutomationJob;
use App\Jobs\SyncTravianAccountJob;
use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\SystemSetting;
use App\Models\Village;
use Illuminate\Support\Facades\DB;

class QueueAccountWork
{
    public function queueManualAccountSync(Account $account): bool
    {
        return $this->queueSync(
            account: $account,
            village: null,
            message: 'Sync requested and queued from dashboard.',
            useReloadAuto: false,
            chainVillageAutomation: false,
        );
    }

    public function queueVillageSync(Village $village, string $message, bool $useReloadAuto = false): bool
    {
        return $this->queueSync(
            account: $village->account,
            village: $village,
            message: $message,
            useReloadAuto: $useReloadAuto,
            chainVillageAutomation: true,
        );
    }

    protected function queueSync(
        Account $account,
        ?Village $village,
        string $message,
        bool $useReloadAuto,
        bool $chainVillageAutomation,
    ): bool {
        if (! SystemSetting::automationEnabled()) {
            return false;
        }

        $queued = DB::transaction(function () use ($account, $village, $message): bool {
            $lockedAccount = Account::query()
                ->whereKey($account->id)
                ->lockForUpdate()
                ->first();

            if (! $lockedAccount instanceof Account || ! $this->accountCanQueueWork($lockedAccount)) {
                return false;
            }

            if ($village instanceof Village && ! $this->villageCanQueueWork($village)) {
                return false;
            }

            if ($this->accountAlreadyHasSyncWork($lockedAccount)) {
                return false;
            }

            $lockedAccount->forceFill([
                'status' => AccountStatus::Syncing,
                'connection_retry_after' => null,
            ])->save();

            ActivityLog::query()->create([
                'account_id' => $lockedAccount->id,
                'village_id' => $village?->id,
                'activity_type' => ActivityType::Sync,
                'status' => ActivityLogStatus::Pending,
                'message' => $message,
                'scheduled_at' => now(),
            ]);

            return true;
        });

        if (! $queued) {
            return false;
        }

        if ($village instanceof Village && $chainVillageAutomation) {
            SyncTravianAccountJob::withChain([
                new RunTravianAutomationJob($account->id, $village->id, false, true),
            ])->dispatch($account->id, $village->id, true, $useReloadAuto);

            return true;
        }

        SyncTravianAccountJob::dispatch($account->id, null, true, $useReloadAuto);

        return true;
    }

    protected function accountCanQueueWork(Account $account): bool
    {
        return $account->is_active
            && ! $account->is_archived;
    }

    protected function villageCanQueueWork(Village $village): bool
    {
        $freshVillage = Village::query()
            ->whereKey($village->id)
            ->where('account_id', $village->account_id)
            ->first();

        return $freshVillage instanceof Village
            && (bool) $freshVillage->is_active;
    }

    protected function accountAlreadyHasSyncWork(Account $account): bool
    {
        if ($account->status === AccountStatus::Syncing) {
            return true;
        }

        return ActivityLog::query()
            ->where('account_id', $account->id)
            ->where('activity_type', ActivityType::Sync->value)
            ->whereIn('status', [ActivityLogStatus::Pending->value, ActivityLogStatus::Running->value])
            ->where('created_at', '>=', now()->subSeconds(max(10, (int) config('travian.automation.request_dedupe_seconds', 30))))
            ->exists();
    }
}
