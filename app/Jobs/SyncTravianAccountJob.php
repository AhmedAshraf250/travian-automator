<?php

namespace App\Jobs;

use App\Application\Accounts\Construction\RunAccountAutomation;
use App\Application\Accounts\Sync\SyncAccountOverview;
use App\Enums\AccountStatus;
use App\Enums\ActivityLogStatus;
use App\Enums\ActivityType;
use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\SystemSetting;
use App\Models\Village;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Runs a full account overview synchronization in the background queue.
 */
class SyncTravianAccountJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The maximum number of attempts for the job.
     */
    public int $tries = 2;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $accountId,
        public ?int $villageId = null,
        public bool $runAutomation = true,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(SyncAccountOverview $syncAccountOverview, RunAccountAutomation $runAccountAutomation): void
    {
        $account = Account::query()->findOrFail($this->accountId);
        $village = $this->villageId !== null
            ? Village::query()->where('account_id', $account->id)->findOrFail($this->villageId)
            : null;

        ActivityLog::query()->create([
            'account_id' => $account->id,
            'village_id' => $village?->id,
            'activity_type' => ActivityType::Sync,
            'status' => ActivityLogStatus::Running,
            'message' => $village instanceof Village
                ? 'Background village sync job started.'
                : 'Background sync job started.',
            'executed_at' => now(),
        ]);

        $syncAccountOverview->handle($account, $village);

        if ($this->runAutomation && SystemSetting::automationEnabled()) {
            $runAccountAutomation->handle($account->fresh(), $this->villageId);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(?Throwable $throwable): void
    {
        $account = Account::query()->find($this->accountId);

        if ($account === null) {
            return;
        }

        $account->forceFill([
            'status' => AccountStatus::Error,
            'last_error_at' => now(),
            'last_error_message' => $throwable?->getMessage(),
        ])->save();

        ActivityLog::query()->create([
            'account_id' => $account->id,
            'village_id' => $this->villageId,
            'activity_type' => ActivityType::Sync,
            'status' => ActivityLogStatus::Failed,
            'message' => $throwable?->getMessage() ?? 'Background sync job failed.',
            'executed_at' => now(),
        ]);
    }
}
