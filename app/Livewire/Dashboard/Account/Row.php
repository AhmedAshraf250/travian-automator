<?php

namespace App\Livewire\Dashboard\Account;

use App\Enums\ActivityLogStatus;
use App\Enums\ActivityType;
use App\Livewire\Dashboard\Concerns\ManagesAutomationControls;
use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\Village;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Reactive;
use Livewire\Component;

class Row extends Component
{
    use ManagesAutomationControls {
        activateAccount as protected activateAccountUsingDashboardControls;
        pauseAccount as protected pauseAccountUsingDashboardControls;
        requestAccountSync as protected requestAccountSyncUsingDashboardControls;
    }

    #[Locked]
    public int $accountId;

    #[Reactive]
    public bool $isExpanded = false;

    #[Reactive]
    public bool $automationEnabled = true;

    #[Reactive]
    public ?string $globalDefaultUserAgent = null;

    #[Reactive]
    public array $globalFieldPriority = [];

    #[Reactive]
    public int $globalFieldLevelCap = 10;

    #[Reactive]
    public bool $globalPrioritizeCropFieldsWhenNegative = true;

    #[Reactive]
    public string $dashboardRevision = '';

    public function activateAccount(int $accountId): void
    {
        $this->activateAccountUsingDashboardControls($accountId);
        $this->dispatch('dashboard-row-updated');
    }

    public function pauseAccount(int $accountId): void
    {
        $this->pauseAccountUsingDashboardControls($accountId);
        $this->dispatch('dashboard-row-updated');
    }

    public function requestAccountSync(int $accountId): void
    {
        $this->requestAccountSyncUsingDashboardControls($accountId);
        $this->dispatch('dashboard-row-updated');
    }

    /** Reactive revisions are invalidated by the dashboard parent. */
    protected function invalidateDashboardRevision(): void {}

    public function render(): View
    {
        $account = $this->account();
        $villages = $this->isExpanded ? $this->villages() : collect();

        return view('livewire.dashboard.account.row', [
            'account' => $account,
            'accountIsExpanded' => $this->isExpanded,
            'automationEnabled' => $this->automationEnabled,
            'globalDefaultUserAgent' => $this->globalDefaultUserAgent,
            'globalFieldPriority' => $this->globalFieldPriority,
            'globalFieldLevelCap' => $this->globalFieldLevelCap,
            'globalPrioritizeCropFieldsWhenNegative' => $this->globalPrioritizeCropFieldsWhenNegative,
            'loadedVillages' => $villages,
        ]);
    }

    protected function account(): Account
    {
        return Account::query()
            ->with([
                'settings',
                'proxies',
                'activeProxy',
                'heroState',
                'latestTravianActivityLog',
            ])
            ->withCount('villages')
            ->findOrFail($this->accountId);
    }

    /**
     * @return Collection<int, Village>
     */
    protected function villages(): Collection
    {
        return Village::query()
            ->where('account_id', $this->accountId)
            ->with([
                'account',
                'settings',
                'resourceState',
                'runtimeState',
                'buildings' => fn ($query) => $query->orderBy('slot_id'),
                'buildingTargets' => fn ($query) => $query->orderBy('priority')->orderBy('slot_id'),
            ])
            ->orderBy('id')
            ->get();
    }

    protected function logManualActivity(Account $account, ?Village $village, string $message): void
    {
        ActivityLog::query()->create([
            'account_id' => $account->id,
            'village_id' => $village?->id,
            'activity_type' => ActivityType::Manual,
            'status' => ActivityLogStatus::Done,
            'message' => $message,
            'executed_at' => now(),
        ]);
    }
}
