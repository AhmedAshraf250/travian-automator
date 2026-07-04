<?php

namespace App\Jobs;

use App\Application\Accounts\Automation\PlanNextAccountAutomation;
use App\Application\Accounts\Connection\AccountConnectionBackoffStarted;
use App\Application\Accounts\Construction\RunAccountAutomation;
use App\Application\Accounts\Sync\SyncAccountOverview;
use App\Enums\AccountStatus;
use App\Enums\ActivityLogStatus;
use App\Enums\ActivityType;
use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\SystemSetting;
use App\Models\Village;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Throwable;

/**
 * Runs one smart automation cycle for a single account.
 *
 * Dispatching this job is separate from importing, manual sync buttons, and
 * scheduler timing. When it runs, it trusts complete local snapshots and
 * performs a sync first only when required data is missing or timers elapsed.
 */
class RunTravianAutomationJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    /**
     * The maximum number of attempts for the job.
     */
    public int $tries = 12;

    /**
     * Let slow proxy-backed sync/build requests finish before timeout handling.
     */
    public int $timeout = 90;

    /**
     * Back off when a manual sync or another automation job owns the account lock.
     *
     * @var list<int>
     */
    public array $backoff = [5, 10, 20, 30, 45, 60];

    /**
     * Keep duplicate account automation jobs out while one is pending or running.
     */
    public int $uniqueFor = 1800;

    /**
     * Create a new job instance.
     *
     * $syncWhenStale controls whether the job should refresh old local data
     * before deciding what action, if any, should happen for this account.
     */
    public function __construct(
        public int $accountId,
        public ?int $villageId = null,
        public bool $syncWhenStale = true,
        public bool $ignoreConnectionBackoff = false,
    ) {
        $this->timeout = max(30, (int) config('travian.automation.job_timeout_seconds', 90));
    }

    /**
     * Identify duplicate automation jobs by account.
     */
    public function uniqueId(): string
    {
        return (string) $this->accountId;
    }

    /**
     * Prevent two workers from automating the same account at the same time.
     *
     * @return list<object>
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping("travian-account:{$this->accountId}"))
                ->shared()
                ->releaseAfter(15)
                ->expireAfter(1800),
        ];
    }

    /**
     * Execute the job.
     */
    public function handle(
        SyncAccountOverview $syncAccountOverview,
        RunAccountAutomation $runAccountAutomation,
        PlanNextAccountAutomation $planNextAccountAutomation,
    ): void {
        $account = Account::query()->findOrFail($this->accountId);
        $village = $this->villageId !== null
            ? Village::query()->where('account_id', $account->id)->findOrFail($this->villageId)
            : null;

        if (
            ! SystemSetting::automationEnabled()
            || ! $account->is_active
            || $account->is_archived
            || $account->isWaitingForConnectionRetry()
        ) {
            return;
        }

        $account->forceFill([
            'automation_dispatched_at' => now(),
        ])->save();

        ActivityLog::query()->create([
            'account_id' => $account->id,
            'village_id' => $village?->id,
            'activity_type' => ActivityType::Build,
            'status' => ActivityLogStatus::Running,
            'message' => $village instanceof Village
                ? 'Checking local village automation plan.'
                : 'Checking local account automation plan.',
            'executed_at' => now(),
        ]);

        try {
            if ($this->syncWhenStale && $this->snapshotIsStale($account, $village)) {
                $syncAccountOverview->handle($account, $village);
            }

            $accountAfterSync = Account::query()->findOrFail($account->id);

            if (
                ! $accountAfterSync->is_active
                || $accountAfterSync->is_archived
                || $accountAfterSync->isWaitingForConnectionRetry()
            ) {
                return;
            }

            $runAccountAutomation->handle($accountAfterSync, $this->villageId);
        } catch (AccountConnectionBackoffStarted) {
            return;
        }

        $freshAccount = Account::query()
            ->with('settings', 'heroState', 'villages.runtimeState')
            ->findOrFail($account->id);

        if ($freshAccount->is_active && ! $freshAccount->is_archived && ! $freshAccount->isWaitingForConnectionRetry()) {
            $freshAccount->forceFill([
                'status' => AccountStatus::Active,
                'last_error_at' => null,
                'last_error_message' => null,
            ]);
        }

        if ($freshAccount->is_active && ! $freshAccount->is_archived && ! $freshAccount->isWaitingForConnectionRetry()) {
            $freshAccount->forceFill([
                'next_automation_at' => $planNextAccountAutomation->handle($freshAccount),
            ]);
        }

        if ($freshAccount->isDirty()) {
            $freshAccount->save();
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
            'activity_type' => ActivityType::Build,
            'status' => ActivityLogStatus::Failed,
            'message' => $throwable?->getMessage() ?? 'Smart automation job failed.',
            'executed_at' => now(),
        ]);
    }

    /**
     * Decide whether the stored account or village snapshot is too incomplete to use.
     *
     * Automation should avoid fixed dorf1/dorf2 refreshes merely because time
     * passed. It refreshes before running only when required data is missing or
     * a saved construction timer has elapsed and needs confirmation.
     */
    protected function snapshotIsStale(Account $account, ?Village $village): bool
    {
        if ($village instanceof Village) {
            $village->loadMissing('runtimeState', 'resourceState');

            return $this->villageSnapshotIsMissing($village)
                || $this->hasElapsedVillageTimer($village);
        }

        if ($account->last_sync_at === null || ! $account->villages()->where('is_active', true)->exists()) {
            return true;
        }

        return $account->villages()
            ->with('runtimeState', 'resourceState')
            ->where('is_active', true)
            ->get()
            ->contains(function (Village $village): bool {
                return $this->villageSnapshotIsMissing($village)
                    || $this->hasElapsedVillageTimer($village);
            })
            || $this->hasElapsedHeroTimer($account);
    }

    /**
     * Determine whether core village data needed for automation is missing.
     */
    protected function villageSnapshotIsMissing(Village $village): bool
    {
        return $village->last_sync_at === null
            || $village->runtimeState === null
            || $village->resourceState === null;
    }

    /**
     * Treat completed stored village timers as stale, because local state only
     * becomes trustworthy after opening the village again.
     */
    protected function hasElapsedVillageTimer(Village $village): bool
    {
        $runtimeState = $village->runtimeState;

        if ($runtimeState === null || $runtimeState->server_reported_at === null) {
            return false;
        }

        foreach ([
            $runtimeState->construction_entries ?? [],
            $runtimeState->movement_entries ?? [],
        ] as $entries) {
            foreach ($entries as $entry) {
                if (isset($entry['remaining_seconds']) && (int) $entry['remaining_seconds'] <= $this->entryElapsedSeconds($entry, $runtimeState->server_reported_at)) {
                    return true;
                }
            }
        }

        $elapsedSeconds = max(0, now()->getTimestamp() - $runtimeState->server_reported_at->getTimestamp());

        if (
            $runtimeState->hero_remaining_seconds !== null
            && $runtimeState->hero_status !== null
            && $runtimeState->hero_status !== 'home'
            && (int) $runtimeState->hero_remaining_seconds <= $elapsedSeconds
        ) {
            return true;
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    protected function entryElapsedSeconds(array $entry, CarbonImmutable $fallbackReportedAt): int
    {
        $recordedAt = $entry['recorded_at'] ?? null;

        if (is_string($recordedAt) && $recordedAt !== '') {
            try {
                return max(0, now()->getTimestamp() - CarbonImmutable::parse($recordedAt)->getTimestamp());
            } catch (Throwable) {
            }
        }

        return max(0, now()->getTimestamp() - $fallbackReportedAt->getTimestamp());
    }

    /**
     * Treat completed account-level hero timers as stale as well.
     */
    protected function hasElapsedHeroTimer(Account $account): bool
    {
        $account->loadMissing('heroState');
        $heroState = $account->heroState;

        if ($heroState === null || $heroState->seen_at === null || $heroState->hero_remaining_seconds === null) {
            return false;
        }

        if (! in_array($heroState->status, ['adventure', 'returning', 'regenerating'], true)) {
            return false;
        }

        $elapsedSeconds = max(0, now()->getTimestamp() - $heroState->seen_at->getTimestamp());

        return (int) $heroState->hero_remaining_seconds <= $elapsedSeconds;
    }
}
