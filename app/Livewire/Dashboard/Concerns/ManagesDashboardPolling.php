<?php

namespace App\Livewire\Dashboard\Concerns;

use App\Application\Accounts\Connection\DispatchDueConnectionRetries;
use App\Application\Accounts\Connection\RotatesAccountProxy;
use App\Enums\AccountStatus;
use App\Enums\ActivityLogStatus;
use App\Enums\ActivityType;
use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\SystemSetting;

trait ManagesDashboardPolling
{
    /**
     * Tracks the latest persisted dashboard state so polling can stay lightweight.
     */
    public string $dashboardRevision = '';

    /**
     * Poll only a tiny local revision marker, and render the full dashboard only when data changed.
     */
    public function refreshDashboardIfChanged(DispatchDueConnectionRetries $dispatchDueConnectionRetries, RotatesAccountProxy $rotatesAccountProxy): void
    {
        if ($this->showProgramSettingsModal || $this->showAccountSettingsModal || $this->showImportModal || $this->showVillageBuildPlanModal || $this->showMarketplaceTransferModal || $this->showVillageDemolitionModal) {
            $this->skipRender();

            return;
        }

        $changed = $rotatesAccountProxy->refreshExpiredCooldowns();

        if (SystemSetting::automationEnabled()) {
            $changed += $this->recoverStaleSyncingAccounts();

            if ($dispatchDueConnectionRetries->handle() > 0) {
                $changed++;
            }
        }

        if ($changed > 0) {
            $this->dashboardRevision = '';
        }

        $latestRevision = $this->computeDashboardRevision();

        if ($latestRevision === $this->dashboardRevision) {
            $this->skipRender();

            return;
        }

        $this->dashboardRevision = $latestRevision;
    }

    protected function recoverStaleSyncingAccounts(): int
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
