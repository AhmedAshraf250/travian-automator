<?php

namespace App\Livewire\Dashboard\Concerns;

use App\Application\Accounts\Automation\RecoverStaleSyncingAccounts;
use App\Application\Accounts\Connection\DispatchDueConnectionRetries;
use App\Application\Accounts\Connection\RotatesAccountProxy;
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
    public function refreshDashboardIfChanged(
        DispatchDueConnectionRetries $dispatchDueConnectionRetries,
        RotatesAccountProxy $rotatesAccountProxy,
        RecoverStaleSyncingAccounts $recoverStaleSyncingAccounts,
    ): void {
        if ($this->dashboardChildModalOpen || $this->showProgramSettingsModal || $this->showImportModal || $this->showVillageBuildPlanModal || $this->showMarketplaceTransferModal || $this->showVillageDemolitionModal) {
            $this->skipRender();

            return;
        }

        $changed = $rotatesAccountProxy->refreshExpiredCooldowns();

        if (SystemSetting::automationEnabled()) {
            $changed += $recoverStaleSyncingAccounts->handle();

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
}
