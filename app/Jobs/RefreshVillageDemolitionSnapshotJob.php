<?php

namespace App\Jobs;

use App\Application\Accounts\Construction\RefreshVillageDemolitionSnapshot;
use App\Enums\ActivityLogStatus;
use App\Enums\ActivityType;
use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\SystemSetting;
use App\Models\Village;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class RefreshVillageDemolitionSnapshotJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 90;

    public function __construct(
        public int $accountId,
        public int $villageId,
    ) {
        $this->timeout = max(30, (int) config('travian.automation.job_timeout_seconds', 90));
    }

    public function handle(RefreshVillageDemolitionSnapshot $refreshVillageDemolitionSnapshot): void
    {
        if (! SystemSetting::automationEnabled()) {
            return;
        }

        $account = Account::query()->findOrFail($this->accountId);
        $village = Village::query()
            ->where('account_id', $account->id)
            ->findOrFail($this->villageId);

        $refreshVillageDemolitionSnapshot->handle($account, $village);

        ActivityLog::query()->create([
            'account_id' => $account->id,
            'village_id' => $village->id,
            'activity_type' => ActivityType::Manual,
            'status' => ActivityLogStatus::Done,
            'message' => 'Building demolition snapshot refreshed.',
            'executed_at' => now(),
        ]);
    }

    public function failed(?Throwable $throwable): void
    {
        ActivityLog::query()->create([
            'account_id' => $this->accountId,
            'village_id' => $this->villageId,
            'activity_type' => ActivityType::Manual,
            'status' => ActivityLogStatus::Failed,
            'message' => 'Building demolition snapshot refresh failed: '.($throwable?->getMessage() ?? 'unknown error'),
            'executed_at' => now(),
        ]);
    }
}
