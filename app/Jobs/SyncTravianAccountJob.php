<?php

namespace App\Jobs;

use App\Application\Accounts\Sync\SyncAccountOverview;
use App\Enums\AccountStatus;
use App\Enums\ActivityLogStatus;
use App\Enums\ActivityType;
use App\Models\Account;
use App\Models\ActivityLog;
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
    ) {}

    /**
     * Execute the job.
     */
    public function handle(SyncAccountOverview $syncAccountOverview): void
    {
        $account = Account::query()->findOrFail($this->accountId);

        ActivityLog::query()->create([
            'account_id' => $account->id,
            'activity_type' => ActivityType::Sync,
            'status' => ActivityLogStatus::Running,
            'message' => 'Background sync job started.',
            'executed_at' => now(),
        ]);

        $syncAccountOverview->handle($account);
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
            'activity_type' => ActivityType::Sync,
            'status' => ActivityLogStatus::Failed,
            'message' => $throwable?->getMessage() ?? 'Background sync job failed.',
            'executed_at' => now(),
        ]);
    }
}
