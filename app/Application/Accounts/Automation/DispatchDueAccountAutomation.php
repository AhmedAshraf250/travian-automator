<?php

namespace App\Application\Accounts\Automation;

use App\Enums\AccountStatus;
use App\Jobs\RunTravianAutomationJob;
use App\Models\Account;
use App\Models\SystemSetting;

class DispatchDueAccountAutomation
{
    /**
     * Create a dispatcher instance.
     */
    public function __construct(
        protected PlanNextAccountAutomation $planNextAccountAutomation,
    ) {}

    /**
     * Queue automation jobs only for accounts whose planned time is due.
     *
     * @return array{queued:int, skipped:int, disabled:bool}
     */
    public function handle(?int $accountId = null, bool $force = false): array
    {
        if (! SystemSetting::automationEnabled()) {
            return ['queued' => 0, 'skipped' => 0, 'disabled' => true];
        }

        $now = now();
        $query = Account::query()
            ->with('settings', 'heroState', 'villages.runtimeState')
            ->where('is_active', true)
            ->where('is_archived', false)
            ->where('status', '!=', AccountStatus::Syncing)
            ->where(function ($query) use ($now): void {
                $query
                    ->whereNull('connection_retry_after')
                    ->orWhere('connection_retry_after', '<=', $now);
            })
            ->when(
                $accountId !== null,
                fn ($query) => $query->whereKey($accountId),
                fn ($query) => $force
                    ? $query
                    : $query->where(function ($query) use ($now): void {
                        $query
                            ->whereNull('next_automation_at')
                            ->orWhere('next_automation_at', '<=', $now);
                    }),
            )
            ->orderByRaw('next_automation_at is null desc')
            ->orderBy('next_automation_at')
            ->orderBy('id')
            ->limit(max(1, (int) config('travian.automation.dispatcher_batch_size', 50)));

        $queued = 0;

        foreach ($query->get() as $account) {
            RunTravianAutomationJob::dispatch($account->id);

            $account->forceFill([
                'automation_dispatched_at' => $now,
                'next_automation_at' => $this->planNextAccountAutomation->handle($account),
            ])->save();

            $queued++;
        }

        return ['queued' => $queued, 'skipped' => 0, 'disabled' => false];
    }
}
