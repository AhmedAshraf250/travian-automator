<?php

namespace App\Livewire\Dashboard;

use App\Application\Accounts\Import\ImportDraftStore;
use App\Enums\ActivityLogStatus;
use App\Enums\ActivityType;
use App\Livewire\Dashboard\Concerns\BuildsDashboardViewData;
use App\Livewire\Dashboard\Concerns\ManagesAccountImports;
use App\Livewire\Dashboard\Concerns\ManagesAutomationControls;
use App\Livewire\Dashboard\Concerns\ManagesDashboardPolling;
use App\Livewire\Dashboard\Concerns\ManagesDashboardShell;
use App\Livewire\Dashboard\Concerns\ManagesMarketplaceTransfers;
use App\Livewire\Dashboard\Concerns\ManagesProgramSettings;
use App\Livewire\Dashboard\Concerns\ManagesVillageDemolition;
use App\Livewire\Dashboard\Concerns\ManagesVillageSettings;
use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\SystemSetting;
use App\Models\Village;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Renders the first automation dashboard with account import and activity visibility.
 */
class Index extends Component
{
    use BuildsDashboardViewData;
    use ManagesAccountImports;
    use ManagesAutomationControls;
    use ManagesDashboardPolling;
    use ManagesDashboardShell;
    use ManagesMarketplaceTransfers;
    use ManagesProgramSettings;
    use ManagesVillageDemolition;
    use ManagesVillageSettings;

    /**
     * Browser events emitted by row islands that still need the dashboard shell.
     *
     * @var array<string, string>
     */
    protected $listeners = [
        'dashboard-toggle-account-expansion' => 'toggleAccountExpansion',
    ];

    public bool $dashboardChildModalOpen = false;

    #[On('dashboard-row-updated')]
    public function markDashboardChanged(): void
    {
        $this->dashboardRevision = '';
    }

    #[On('dashboard-modal-visibility-changed')]
    public function updateDashboardModalVisibility(bool $open): void
    {
        $this->dashboardChildModalOpen = $open;
        $this->skipRender();
    }

    /**
     * Mount the dashboard component.
     */
    public function mount(ImportDraftStore $draftStore): void
    {
        $this->bulkImportDraft = $draftStore->get();
        $this->defaultUserAgent = SystemSetting::defaultUserAgent() ?? '';
        $constructionDefaults = SystemSetting::constructionDefaults();
        $this->globalFieldPriorityDraft = $constructionDefaults['field_priority'];
        $this->globalPrioritizeCropFieldsWhenNegativeDraft = $constructionDefaults['prioritize_crop_fields_when_negative'];
        $this->globalFieldLevelCapDraft = (int) $constructionDefaults['field_level_cap'];
        $this->globalHeroDefaultsDraft = SystemSetting::heroDefaults();
        $this->globalTradeDefaultsDraft = SystemSetting::tradeDefaults();
        $this->globalTradeMaxDurationMinutesDraft = $this->secondsToWholeMinutes((int) ($this->globalTradeDefaultsDraft['max_duration_seconds'] ?? 18000));
        if ($this->dashboardRevision === '') {
            $this->dashboardRevision = $this->computeDashboardRevision();
        }
    }

    /**
     * Render the dashboard component.
     */
    public function render(): View
    {
        if (! Schema::hasTable('accounts') || ! Schema::hasTable('activity_logs')) {
            return view('livewire.dashboard.index', [
                ...$this->emptyDashboardState(),
                'importPreviewRows' => $this->showImportModal ? $this->buildImportPreviewRows() : [],
            ]);
        }

        $accounts = $this->loadAccounts();
        $activityLogs = $this->showActivityLog ? $this->loadActivityLogs() : collect();
        $activityLogCount = $this->showActivityLog ? $activityLogs->count() : $this->activityLogCount();
        $marketplaceTransferVillages = $this->showMarketplaceTransferModal ? $this->marketplaceTransferVillages() : collect();
        $marketplaceTransferCapacity = $this->showMarketplaceTransferModal
            ? $this->marketplaceTransferCapacity()
            : [
                'available_merchants' => null,
                'merchant_capacity' => $this->merchantCapacityForTribe(null),
                'total_capacity' => null,
                'resources' => [
                    'wood' => null,
                    'clay' => null,
                    'iron' => null,
                    'crop' => null,
                ],
                'reported_at' => null,
            ];
        $demolitionSnapshot = $this->showVillageDemolitionModal ? $this->demolitionSnapshot() : [];
        $demolitionBuildings = $this->showVillageDemolitionModal ? $this->demolitionBuildings() : collect();

        return view('livewire.dashboard.index', [
            'accounts' => $accounts,
            'activityLogs' => $activityLogs,
            'activityLogCount' => $activityLogCount,
            'marketplaceTransferVillages' => $marketplaceTransferVillages,
            'marketplaceTransferCapacity' => $marketplaceTransferCapacity,
            'demolitionSnapshot' => $demolitionSnapshot,
            'demolitionBuildings' => $demolitionBuildings,
            'stats' => $this->buildStats($accounts),
            'importPreviewRows' => $this->showImportModal ? $this->buildImportPreviewRows() : [],
            ...$this->buildSystemSettingsViewData(),
        ]);
    }

    /**
     * Store a user-facing manual log entry.
     */
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
