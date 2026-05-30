<?php

namespace App\Jobs;

use App\Application\Accounts\Sync\SyncAccountOverview;
use App\Enums\AccountStatus;
use App\Enums\ActivityLogStatus;
use App\Enums\ActivityType;
use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\Village;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Runs a full account overview synchronization in the background queue.
 */
class SyncTravianAccountJob implements ShouldBeUnique, ShouldQueue
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
     * Keep duplicate account jobs out of the queue while one is pending or running.
     */
    public int $uniqueFor = 1800;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $accountId,
        public ?int $villageId = null,
    ) {}

    /**
     * Identify duplicate jobs by account, not by village, to keep one session per account.
     */
    public function uniqueId(): string
    {
        return (string) $this->accountId;
    }

    /**
     * Prevent two workers from processing the same account at the same time.
     *
     * @return list<object>
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping("travian-account:{$this->accountId}"))
                ->expireAfter(1800),
        ];
    }

    /**
     * Execute the job.
     */
    public function handle(SyncAccountOverview $syncAccountOverview): void
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
