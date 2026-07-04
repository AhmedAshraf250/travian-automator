<?php

namespace App\Livewire\Dashboard;

use App\Application\Accounts\Connection\DispatchDueConnectionRetries;
use App\Application\Accounts\Connection\RotatesAccountProxy;
use App\Application\Accounts\Import\BulkAccountImportParser;
use App\Application\Accounts\Import\ImportBulkAccounts;
use App\Application\Accounts\Import\ImportDraftStore;
use App\Application\Travian\TravianBuildingCatalog;
use App\Enums\AccountStatus;
use App\Enums\ActivityLogStatus;
use App\Enums\ActivityType;
use App\Enums\VillageCelebrationType;
use App\Jobs\CancelVillageDemolitionJob;
use App\Jobs\DemolishVillageBuildingJob;
use App\Jobs\RefreshVillageDemolitionSnapshotJob;
use App\Jobs\RefreshVillageMarketplaceSnapshotJob;
use App\Jobs\RunTravianAutomationJob;
use App\Jobs\SendManualMarketplaceTransferJob;
use App\Jobs\SyncTravianAccountJob;
use App\Models\Account;
use App\Models\AccountProxy;
use App\Models\AccountSetting;
use App\Models\ActivityLog;
use App\Models\SystemSetting;
use App\Models\Village;
use App\Models\VillageBuilding;
use App\Models\VillageResourceState;
use App\Models\VillageSetting;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Throwable;

/**
 * Renders the first automation dashboard with account import and activity visibility.
 */
class Index extends Component
{
    /**
     * Controls the program settings modal visibility.
     */
    public bool $showProgramSettingsModal = false;

    /**
     * Controls the account settings modal visibility.
     */
    public bool $showAccountSettingsModal = false;

    /**
     * Controls the bulk import modal visibility.
     */
    public bool $showImportModal = false;

    /**
     * Controls the village build plan modal visibility.
     */
    public bool $showVillageBuildPlanModal = false;

    /**
     * Keeps the activity log panel visible or hidden.
     */
    public bool $showActivityLog = true;

    /**
     * Stores the activity log drawer height as a viewport percentage.
     */
    public int $activityLogHeight = 22;

    /**
     * Stores which account rows are expanded in the UI.
     *
     * @var array<int, bool>
     */
    public array $expandedAccounts = [];

    /**
     * Holds the textarea draft for bulk account import.
     */
    public string $bulkImportDraft = '';

    /**
     * Stores the global fallback user agent used when an account has none.
     */
    public string $defaultUserAgent = '';

    /**
     * Stores the global hero automation defaults.
     *
     * @var array{
     *     adventures_enabled: bool,
     *     min_health: int,
     *     revive_enabled: bool,
     *     attribute_upgrade_enabled: bool,
     *     attribute_weights: array{power: int, offBonus: int, defBonus: int, productionPoints: int}
     * }
     */
    public array $globalHeroDefaultsDraft = [];

    /**
     * Stores the global editable field priority defaults.
     *
     * @var array{wood: int, clay: int, iron: int, crop: int}
     */
    public array $globalFieldPriorityDraft = [];

    /**
     * Stores whether global defaults should prefer crop fields during negative crop production.
     */
    public bool $globalPrioritizeCropFieldsWhenNegativeDraft = true;

    public int $globalFieldLevelCapDraft = 10;

    /**
     * @var array{max_duration_seconds: int}
     */
    public array $globalTradeDefaultsDraft = [];

    public int $globalTradeMaxDurationMinutesDraft = 300;

    /**
     * Tracks the latest persisted dashboard state so polling can stay lightweight.
     */
    public string $dashboardRevision = '';

    /**
     * Stores the currently edited village identifier for the plan modal.
     */
    public ?int $editingVillageId = null;

    /**
     * Stores the currently edited account identifier for the account modal.
     */
    public ?int $editingAccountId = null;

    /**
     * Stores the current account username for the account modal header.
     */
    public string $editingAccountUsername = '';

    /**
     * Stores the active account settings modal tab.
     */
    public string $accountSettingsTab = 'account';

    /**
     * Stores the active program settings modal tab.
     */
    public string $programSettingsTab = 'generals';

    /**
     * Stores the active village settings modal tab.
     */
    public string $villageSettingsTab = 'generals';

    /**
     * Stores whether the edited account inherits the program user agent.
     */
    public bool $accountInheritUserAgentDraft = true;

    /**
     * Stores the edited account user-agent override.
     */
    public string $accountUserAgentDraft = '';

    /**
     * Stores account-level task reward collection toggle.
     */
    public bool $accountAcceptQuestsDraft = true;

    /**
     * Stores the edited proxy pool rows for the account settings modal.
     *
     * @var list<array{id:int|null, scheme:string, host:string, port:string, username:string, password:string, status:string}>
     */
    public array $accountProxyDrafts = [];

    /**
     * Stores the selected proxy row key for the account settings modal.
     */
    public string $accountActiveProxyDraft = 'direct';

    /**
     * Stores whether the edited account inherits global hero settings.
     */
    public bool $accountHeroUseGlobalSettingsDraft = true;

    /**
     * Stores account-level hero adventure toggle.
     */
    public bool $accountHeroAdventuresEnabledDraft = false;

    /**
     * Stores account-level hero minimum health.
     */
    public int $accountHeroMinHealthDraft = 40;

    /**
     * Stores account-level revive toggle.
     */
    public bool $accountHeroReviveEnabledDraft = false;

    /**
     * Stores account-level attribute upgrade toggle.
     */
    public bool $accountHeroAttributeUpgradeEnabledDraft = false;

    /**
     * Stores account-level hero attribute weights.
     *
     * @var array{power: int, offBonus: int, defBonus: int, productionPoints: int}
     */
    public array $accountHeroAttributeWeightsDraft = [];

    public bool $showMarketplaceTransferModal = false;

    public bool $showVillageDemolitionModal = false;

    public string $marketplaceTransferTab = 'send';

    public ?int $marketplaceSourceVillageId = null;

    public string $marketplaceSourceVillageLabel = '';

    public ?int $demolitionVillageId = null;

    public string $demolitionVillageLabel = '';

    public ?int $demolitionSelectedSlotId = null;

    public string $marketplaceDestinationMode = 'owned';

    public ?int $marketplaceDestinationVillageId = null;

    public string $marketplaceDestinationX = '';

    public string $marketplaceDestinationY = '';

    public int $marketplaceWoodDraft = 0;

    public int $marketplaceClayDraft = 0;

    public int $marketplaceIronDraft = 0;

    public int $marketplaceCropDraft = 0;

    /**
     * Temporarily polls the TR capacity panel after a manual refresh is queued.
     */
    public ?int $marketplaceSnapshotRefreshPollUntil = null;

    /**
     * Stores the current village name for the plan modal header.
     */
    public string $editingVillageName = '';

    /**
     * Stores the currently edited village tribe id.
     */
    public ?int $editingVillageTribeId = null;

    /**
     * Stores the tribe label shown in the plan modal header.
     */
    public string $editingVillageTribeLabel = '';

    /**
     * Stores whether the edited village is the account capital.
     */
    public bool $editingVillageIsCapital = false;

    /**
     * Stores whether field automation is enabled for the edited village.
     */
    public bool $villageFieldsAutomationDraft = true;

    /**
     * Stores whether building automation is enabled for the edited village.
     */
    public bool $villageBuildingsAutomationDraft = true;

    /**
     * Stores whether the edited village inherits field priority from program settings.
     */
    public bool $villageInheritProgramPriorityDraft = true;

    public string $villageFieldLevelCapModeDraft = VillageSetting::FieldCapInherit;

    public int $villageFieldLevelCapDraft = 10;

    /**
     * Stores whether the edited village can supply resources to other villages.
     */
    public bool $villageSendResourcesDraft = true;

    /**
     * Stores whether the edited village accepts resources from other villages.
     */
    public bool $villageSupplyResourcesDraft = true;

    /**
     * Stores whether the edited village may use hero resource items before marketplace support.
     */
    public bool $villageHeroResourcesDraft = true;

    /**
     * Stores whether the edited village may receive crop while crop production is negative.
     */
    public bool $villageSupplyNegativeCropDraft = true;

    /**
     * Stores the max one-way merchant travel time allowed for this village.
     */
    public int $villageTradeMaxDurationMinutesDraft = 300;

    /**
     * Stores the minimum stock percentage required before the edited village can send one resource.
     */
    public int $villageSendMinResourcePercentageDraft = 30;

    /**
     * Stores the stock percentage the edited village keeps after sending resources.
     */
    public int $villageSendReserveResourcePercentageDraft = 10;

    /**
     * Stores whether celebration automation is enabled for the edited village.
     */
    public bool $villageCelebrationEnabledDraft = false;

    /**
     * Stores whether troop training automation is enabled for the edited village.
     */
    public bool $villageTroopTrainingEnabledDraft = false;

    /**
     * Stores the preferred celebration type for the edited village.
     */
    public string $villageCelebrationTypeDraft = 'small';

    /**
     * Stores the minimum culture points required before starting a celebration.
     */
    public int $villageCelebrationMinimumCulturePointsDraft = 200;

    /**
     * Stores the current celebration readiness warning for the edited village.
     */
    public string $villageCelebrationReadinessMessage = '';

    /**
     * Stores whether negative crop production should temporarily prefer crop fields.
     */
    public bool $villagePrioritizeCropFieldsWhenNegativeDraft = true;

    /**
     * Stores the editable field priority draft.
     *
     * @var array{wood: int, clay: int, iron: int, crop: int}
     */
    public array $villageFieldPriorityDraft = [];

    /**
     * Stores the editable building plan draft for slots 19..40.
     *
     * @var array<int, array<string, mixed>>
     */
    public array $villageBuildingPlanDraft = [];

    /**
     * Stores the selectable building options per slot.
     *
     * @var array<int, list<array{gid: int, label: string, category: int|null}>>
     */
    public array $slotBuildingOptions = [];

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
        $this->dashboardRevision = $this->computeDashboardRevision();
    }

    /**
     * Poll only a tiny local revision marker, and render the full dashboard only when data changed.
     */
    public function refreshDashboardIfChanged(DispatchDueConnectionRetries $dispatchDueConnectionRetries, RotatesAccountProxy $rotatesAccountProxy): void
    {
        if ($this->showProgramSettingsModal || $this->showAccountSettingsModal || $this->showImportModal || $this->showVillageBuildPlanModal) {
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

    /**
     * Persist the draft whenever the textarea changes.
     */
    public function updatedBulkImportDraft(ImportDraftStore $draftStore): void
    {
        $draftStore->put($this->bulkImportDraft);
    }

    /**
     * Toggle the account row expansion state.
     */
    public function toggleAccountExpansion(int $accountId): void
    {
        $currentState = $this->expandedAccounts[$accountId] ?? false;

        $this->expandedAccounts[$accountId] = ! $currentState;
    }

    /**
     * Toggle the activity log panel.
     */
    public function toggleActivityLog(): void
    {
        $this->showActivityLog = ! $this->showActivityLog;
    }

    /**
     * Increase the activity log drawer height.
     */
    public function increaseActivityLogHeight(): void
    {
        $this->activityLogHeight = min(36, $this->activityLogHeight + 4);
    }

    /**
     * Decrease the activity log drawer height.
     */
    public function decreaseActivityLogHeight(): void
    {
        $this->activityLogHeight = max(16, $this->activityLogHeight - 4);
    }

    /**
     * Open the bulk import modal.
     */
    public function openImportModal(): void
    {
        $this->showImportModal = true;
    }

    /**
     * Open the program settings modal.
     */
    public function openProgramSettingsModal(): void
    {
        $this->defaultUserAgent = SystemSetting::defaultUserAgent() ?? '';
        $constructionDefaults = SystemSetting::constructionDefaults();
        $this->globalFieldPriorityDraft = $constructionDefaults['field_priority'];
        $this->globalPrioritizeCropFieldsWhenNegativeDraft = $constructionDefaults['prioritize_crop_fields_when_negative'];
        $this->globalFieldLevelCapDraft = (int) $constructionDefaults['field_level_cap'];
        $this->globalHeroDefaultsDraft = SystemSetting::heroDefaults();
        $this->globalTradeDefaultsDraft = SystemSetting::tradeDefaults();
        $this->globalTradeMaxDurationMinutesDraft = $this->secondsToWholeMinutes((int) ($this->globalTradeDefaultsDraft['max_duration_seconds'] ?? 18000));
        $this->programSettingsTab = 'generals';
        $this->showProgramSettingsModal = true;
    }

    /**
     * Close the bulk import modal.
     */
    public function closeImportModal(): void
    {
        $this->showImportModal = false;
    }

    /**
     * Close the program settings modal.
     */
    public function closeProgramSettingsModal(): void
    {
        $this->showProgramSettingsModal = false;
        $this->programSettingsTab = 'generals';
    }

    /**
     * Open the per-account settings modal.
     */
    public function openAccountSettingsModal(int $accountId): void
    {
        $account = Account::query()
            ->with(['settings', 'proxies'])
            ->findOrFail($accountId);
        $settings = $account->settings ?? $account->settings()->create([
            'resource_priorities' => AccountSetting::defaultResourcePriorities(),
        ]);

        $this->editingAccountId = $account->id;
        $this->editingAccountUsername = $account->username;
        $this->accountSettingsTab = 'account';
        $this->accountInheritUserAgentDraft = trim((string) $account->user_agent) === '';
        $this->accountUserAgentDraft = (string) ($account->user_agent ?? '');
        $this->accountAcceptQuestsDraft = (bool) $settings->accept_quests;
        $this->ensureLegacyAccountProxyIsPooled($account);
        app(RotatesAccountProxy::class)->refreshExpiredCooldowns($account);
        $account->refresh()->load('proxies');
        $this->accountProxyDrafts = $this->buildAccountProxyDrafts($account);
        $this->accountActiveProxyDraft = $this->resolveAccountActiveProxyDraft($account);
        $this->accountHeroUseGlobalSettingsDraft = (bool) $settings->hero_use_global_settings;
        $this->accountHeroAdventuresEnabledDraft = (bool) $settings->hero_adventures_enabled;
        $this->accountHeroMinHealthDraft = (int) ($settings->hero_min_health ?? 40);
        $this->accountHeroReviveEnabledDraft = (bool) $settings->hero_revive_enabled;
        $this->accountHeroAttributeUpgradeEnabledDraft = (bool) $settings->hero_attribute_upgrade_enabled;
        $this->accountHeroAttributeWeightsDraft = $this->normalizeHeroAttributeWeights($settings->hero_attribute_weights);
        $this->showAccountSettingsModal = true;
    }

    /**
     * Close the per-account settings modal.
     */
    public function closeAccountSettingsModal(): void
    {
        $this->resetAccountSettingsState();
    }

    /**
     * Switch the account settings modal tab.
     */
    public function setAccountSettingsTab(string $tab): void
    {
        if (! in_array($tab, ['account', 'proxies', 'hero'], true)) {
            return;
        }

        $this->accountSettingsTab = $tab;
    }

    /**
     * Add one editable proxy row to the account settings modal.
     */
    public function addAccountProxyDraft(): void
    {
        $this->accountProxyDrafts[] = [
            'id' => null,
            'scheme' => 'socks5',
            'host' => '',
            'port' => '',
            'username' => '',
            'password' => '',
            'status' => AccountProxy::StatusActive,
            'failure_count' => 0,
            'lifetime_failure_count' => 0,
            'cooldown_until' => null,
            'last_error_message' => null,
        ];

        $this->accountActiveProxyDraft = 'new:'.(array_key_last($this->accountProxyDrafts) ?? 0);
    }

    /**
     * Remove one editable proxy row from the account settings modal.
     */
    public function removeAccountProxyDraft(int $index): void
    {
        if (! array_key_exists($index, $this->accountProxyDrafts)) {
            return;
        }

        $removedProxyId = $this->accountProxyDrafts[$index]['id'] ?? null;

        array_splice($this->accountProxyDrafts, $index, 1);

        if ($this->accountActiveProxyDraft === 'new:'.$index || ($removedProxyId !== null && $this->accountActiveProxyDraft === 'proxy:'.$removedProxyId)) {
            $this->accountActiveProxyDraft = 'direct';
        }
    }

    /**
     * Switch the program settings modal tab.
     */
    public function setProgramSettingsTab(string $tab): void
    {
        if (! in_array($tab, ['generals', 'hero', 'troops', 'merchants'], true)) {
            return;
        }

        $this->programSettingsTab = $tab;
    }

    /**
     * Switch the village settings modal tab.
     */
    public function setVillageSettingsTab(string $tab): void
    {
        if (! in_array($tab, ['generals', 'layouts', 'troops', 'celebrations', 'trading'], true)) {
            return;
        }

        $this->villageSettingsTab = $tab;
    }

    /**
     * Refresh celebration readiness when the enable toggle changes.
     */
    public function updatedVillageCelebrationEnabledDraft(): void
    {
        if ($this->villageCelebrationEnabledDraft && ! in_array($this->villageCelebrationTypeDraft, [VillageCelebrationType::Small->value, VillageCelebrationType::Great->value], true)) {
            $this->villageCelebrationTypeDraft = VillageCelebrationType::Small->value;
        }

        $this->refreshVillageCelebrationReadinessMessage();
    }

    /**
     * Refresh celebration readiness when the preferred type changes.
     */
    public function updatedVillageCelebrationTypeDraft(): void
    {
        $this->refreshVillageCelebrationReadinessMessage();
    }

    /**
     * Open the per-village settings modal.
     */
    public function openVillageSettingsModal(int $villageId): void
    {
        $this->openVillageBuildPlanModal($villageId);
    }

    /**
     * Open the per-village build plan modal.
     */
    public function openVillageBuildPlanModal(int $villageId): void
    {
        $village = Village::query()
            ->with([
                'account',
                'settings',
                'runtimeState',
                'buildings' => fn ($query) => $query->orderBy('slot_id'),
                'buildingTargets' => fn ($query) => $query->orderBy('priority')->orderBy('slot_id'),
            ])
            ->findOrFail($villageId);

        $settings = $village->settings ?? $village->settings()->create([
            'field_priority' => VillageSetting::defaultFieldPriority(),
        ]);
        $tribeId = $village->runtimeState?->tribe_id !== null ? (int) $village->runtimeState->tribe_id : null;
        $fieldLevelCapMode = in_array((string) $settings->field_level_cap_mode, VillageSetting::fieldLevelCapModes(), true)
            ? (string) $settings->field_level_cap_mode
            : VillageSetting::FieldCapInherit;
        $fieldLevelCapMode = $fieldLevelCapMode === VillageSetting::FieldCapDisabled
            ? VillageSetting::FieldCapInherit
            : $fieldLevelCapMode;
        $globalFieldLevelCap = (int) (SystemSetting::constructionDefaults()['field_level_cap'] ?? SystemSetting::defaultFieldLevelCap());

        $this->editingVillageId = $village->id;
        $this->editingVillageName = $village->name;
        $this->editingVillageIsCapital = (bool) $village->is_capital;
        $this->editingVillageTribeId = $tribeId;
        $this->editingVillageTribeLabel = $this->resolveTribeLabel($tribeId);
        $this->villageSettingsTab = 'generals';
        $this->villageFieldPriorityDraft = $this->normalizeFieldPriorityDraft($settings->field_priority);
        $this->villageFieldLevelCapModeDraft = $fieldLevelCapMode;
        $this->villageFieldLevelCapDraft = $this->clampVillageFieldLevelCap($village, (int) ($settings->field_level_cap ?? $globalFieldLevelCap));
        $this->villageFieldsAutomationDraft = ! (bool) $settings->pause_fields;
        $this->villageBuildingsAutomationDraft = ! (bool) $settings->pause_buildings;
        $this->villageInheritProgramPriorityDraft = (bool) $settings->inherit_from_account;
        $this->villageSendResourcesDraft = (bool) $settings->send_enabled;
        $this->villageSupplyResourcesDraft = (bool) $settings->support_enabled;
        $this->villageHeroResourcesDraft = (bool) $settings->hero_resources_enabled;
        $this->villageSupplyNegativeCropDraft = (bool) $settings->supply_negative_crop_enabled;
        $this->villageSendMinResourcePercentageDraft = max(0, min(100, (int) ($settings->send_min_resource_percentage ?? 30)));
        $this->villageSendReserveResourcePercentageDraft = max(0, min(100, (int) ($settings->send_reserve_resource_percentage ?? 10)));
        $this->villageTradeMaxDurationMinutesDraft = $this->secondsToWholeMinutes(
            (int) ($settings->trade_max_duration_seconds ?? VillageSetting::defaultTradeMaxDurationSeconds()),
        );
        $this->villageCelebrationEnabledDraft = (bool) $settings->celebration_enabled;
        $this->villageCelebrationTypeDraft = $settings->celebration_type === VillageCelebrationType::Great
            ? VillageCelebrationType::Great->value
            : VillageSetting::defaultCelebrationType()->value;
        $this->villageCelebrationMinimumCulturePointsDraft = max(
            0,
            (int) ($settings->celebration_min_culture_points ?? VillageSetting::defaultCelebrationMinCulturePoints()),
        );
        $this->villageTroopTrainingEnabledDraft = (bool) $settings->troop_training_enabled;
        $this->villagePrioritizeCropFieldsWhenNegativeDraft = (bool) $settings->prioritize_crop_fields_when_negative;
        $this->slotBuildingOptions = $this->buildSlotBuildingOptions($village, $tribeId);
        $this->villageBuildingPlanDraft = $this->buildVillagePlanDraft($village, $tribeId);
        $this->updateVillageCelebrationReadinessMessage($village);
        $this->showVillageBuildPlanModal = true;
    }

    /**
     * Close the per-village settings modal.
     */
    public function closeVillageSettingsModal(): void
    {
        $this->closeVillageBuildPlanModal();
    }

    /**
     * Close the per-village build plan modal.
     */
    public function closeVillageBuildPlanModal(): void
    {
        $this->resetVillageBuildPlanState();
    }

    /**
     * Persist the edited village settings modal.
     */
    public function saveVillageSettings(): void
    {
        $this->saveVillageBuildPlan();
    }

    /**
     * Persist the edited village build plan.
     */
    public function saveVillageBuildPlan(): void
    {
        $this->validate([
            'editingVillageId' => ['required', 'integer', 'exists:villages,id'],
            'villageFieldPriorityDraft.wood' => ['required', 'integer', 'min:1', 'max:4'],
            'villageFieldPriorityDraft.clay' => ['required', 'integer', 'min:1', 'max:4'],
            'villageFieldPriorityDraft.iron' => ['required', 'integer', 'min:1', 'max:4'],
            'villageFieldPriorityDraft.crop' => ['required', 'integer', 'min:1', 'max:4'],
            'villageFieldLevelCapModeDraft' => ['required', 'string', 'in:inherit,custom'],
            'villageFieldLevelCapDraft' => ['required', 'integer', 'min:1', 'max:20'],
            'villageFieldsAutomationDraft' => ['boolean'],
            'villageBuildingsAutomationDraft' => ['boolean'],
            'villageInheritProgramPriorityDraft' => ['boolean'],
            'villageSendResourcesDraft' => ['boolean'],
            'villageSupplyResourcesDraft' => ['boolean'],
            'villageHeroResourcesDraft' => ['boolean'],
            'villageSupplyNegativeCropDraft' => ['boolean'],
            'villageSendMinResourcePercentageDraft' => ['required', 'integer', 'min:0', 'max:100'],
            'villageSendReserveResourcePercentageDraft' => ['required', 'integer', 'min:0', 'max:100'],
            'villageTradeMaxDurationMinutesDraft' => ['required', 'integer', 'min:1', 'max:10080'],
            'villageCelebrationEnabledDraft' => ['boolean'],
            'villageCelebrationTypeDraft' => ['required', 'string', 'in:small,great'],
            'villageCelebrationMinimumCulturePointsDraft' => ['required', 'integer', 'min:0', 'max:2000'],
            'villageTroopTrainingEnabledDraft' => ['boolean'],
            'villagePrioritizeCropFieldsWhenNegativeDraft' => ['boolean'],
            'villageBuildingPlanDraft' => ['array'],
            'villageBuildingPlanDraft.*.slot_id' => ['required', 'integer', 'min:19', 'max:40'],
            'villageBuildingPlanDraft.*.building_gid' => ['nullable', 'integer', 'min:0'],
            'villageBuildingPlanDraft.*.target_level' => ['nullable', 'integer', 'min:0', 'max:20'],
            'villageBuildingPlanDraft.*.priority' => ['nullable', 'integer', 'min:1', 'max:4'],
            'villageBuildingPlanDraft.*.is_enabled' => ['boolean'],
            'villageBuildingPlanDraft.*.current_gid' => ['nullable', 'integer', 'min:0'],
            'villageBuildingPlanDraft.*.current_level' => ['nullable', 'integer', 'min:0', 'max:20'],
        ]);

        $village = Village::query()
            ->with([
                'account',
                'settings',
                'runtimeState',
                'buildings' => fn ($query) => $query->orderBy('slot_id'),
                'buildingTargets' => fn ($query) => $query->orderBy('priority')->orderBy('slot_id'),
            ])
            ->findOrFail((int) $this->editingVillageId);

        $settings = $village->settings ?? $village->settings()->create([
            'field_priority' => VillageSetting::defaultFieldPriority(),
        ]);
        $tribeId = $village->runtimeState?->tribe_id !== null ? (int) $village->runtimeState->tribe_id : null;
        $fieldPriority = $this->normalizeFieldPriorityDraft($this->villageFieldPriorityDraft);
        $fieldLevelCapMode = in_array($this->villageFieldLevelCapModeDraft, VillageSetting::fieldLevelCapModes(), true)
            ? $this->villageFieldLevelCapModeDraft
            : VillageSetting::FieldCapInherit;
        $fieldLevelCap = $this->clampVillageFieldLevelCap($village, (int) $this->villageFieldLevelCapDraft);

        $this->updateVillageCelebrationReadinessMessage($village);

        if ($this->villageCelebrationReadinessMessage !== '') {
            throw ValidationException::withMessages([
                ($this->villageCelebrationTypeDraft === VillageCelebrationType::Great->value
                    ? 'villageCelebrationTypeDraft'
                    : 'villageCelebrationEnabledDraft') => $this->villageCelebrationReadinessMessage,
            ]);
        }

        $settings->forceFill([
            'field_priority' => $fieldPriority,
            'field_level_cap_mode' => $fieldLevelCapMode,
            'field_level_cap' => $fieldLevelCapMode === VillageSetting::FieldCapCustom ? $fieldLevelCap : null,
            'inherit_from_account' => $this->villageInheritProgramPriorityDraft,
            'pause_fields' => ! $this->villageFieldsAutomationDraft,
            'pause_buildings' => ! $this->villageBuildingsAutomationDraft,
            'send_enabled' => $this->villageSendResourcesDraft,
            'support_enabled' => $this->villageSupplyResourcesDraft,
            'hero_resources_enabled' => $this->villageHeroResourcesDraft,
            'supply_negative_crop_enabled' => $this->villageSupplyNegativeCropDraft,
            'send_min_resource_percentage' => max(0, min(100, (int) $this->villageSendMinResourcePercentageDraft)),
            'send_reserve_resource_percentage' => max(0, min(100, (int) $this->villageSendReserveResourcePercentageDraft)),
            'trade_max_duration_seconds' => $this->minutesToSeconds($this->villageTradeMaxDurationMinutesDraft),
            'celebration_enabled' => $this->villageCelebrationEnabledDraft,
            'celebration_type' => VillageCelebrationType::from($this->villageCelebrationTypeDraft),
            'celebration_min_culture_points' => $this->villageCelebrationMinimumCulturePointsDraft,
            'troop_training_enabled' => $this->villageTroopTrainingEnabledDraft,
            'prioritize_crop_fields_when_negative' => $this->villagePrioritizeCropFieldsWhenNegativeDraft,
        ])->save();

        $currentSlots = $village->buildings->keyBy('slot_id');

        foreach ($this->villageBuildingPlanDraft as $draftKey => $row) {
            if (! is_array($row)) {
                continue;
            }

            $slotId = (int) ($row['slot_id'] ?? $draftKey);
            $currentSlot = $currentSlots->get($slotId);
            $currentGid = $currentSlot instanceof VillageBuilding
                ? (int) $currentSlot->building_gid
                : (int) ($row['current_gid'] ?? 0);
            $currentLevel = $currentSlot instanceof VillageBuilding
                ? (int) $currentSlot->current_level
                : (int) ($row['current_level'] ?? 0);
            $targetLevel = (int) ($row['target_level'] ?? 0);
            $buildingGid = (int) ($row['building_gid'] ?? 0);
            $fixedSlotGid = TravianBuildingCatalog::fixedSlotGidForSlot($slotId, $tribeId);
            $isEnabled = (bool) ($row['is_enabled'] ?? true);

            if ($currentSlot instanceof VillageBuilding) {
                $currentSlot->forceFill([
                    'automation_enabled' => $isEnabled,
                ])->save();
            }

            if ($targetLevel > 0 && $buildingGid === 0 && $currentGid !== 0) {
                $buildingGid = $currentGid;
            }

            if ($targetLevel <= 0) {
                $village->buildingTargets()->where('slot_id', $slotId)->delete();

                continue;
            }

            if ($buildingGid === 0) {
                throw ValidationException::withMessages([
                    "villageBuildingPlanDraft.{$slotId}.building_gid" => 'Choose a building before setting a target level.',
                ]);
            }

            if ($fixedSlotGid !== null && $buildingGid !== $fixedSlotGid) {
                throw ValidationException::withMessages([
                    "villageBuildingPlanDraft.{$slotId}.building_gid" => 'This slot is locked to a fixed Travian building.',
                ]);
            }

            if ($currentGid !== 0 && $buildingGid !== $currentGid) {
                throw ValidationException::withMessages([
                    "villageBuildingPlanDraft.{$slotId}.building_gid" => 'An occupied slot can only target the building already placed there.',
                ]);
            }

            if ($currentGid === 0 && ! TravianBuildingCatalog::supportsTribe($buildingGid, $tribeId)) {
                throw ValidationException::withMessages([
                    "villageBuildingPlanDraft.{$slotId}.building_gid" => 'This building is not available for the current tribe.',
                ]);
            }

            if ($currentGid !== 0 && $targetLevel < $currentLevel) {
                $village->buildingTargets()->where('slot_id', $slotId)->delete();

                continue;
            }

            $buildingName = TravianBuildingCatalog::nameForGid($buildingGid);

            if ($buildingName === null) {
                throw ValidationException::withMessages([
                    "villageBuildingPlanDraft.{$slotId}.building_gid" => 'Unknown building gid selected for this slot.',
                ]);
            }

            $targetLevel = $this->clampBuildingTargetLevel($buildingGid, $targetLevel);

            $village->buildingTargets()->updateOrCreate(
                [
                    'slot_id' => $slotId,
                ],
                [
                    'building_gid' => $buildingGid,
                    'building_type' => $buildingName,
                    'target_level' => $targetLevel,
                    'priority' => max(1, min(4, (int) ($row['priority'] ?? 4))),
                    'is_enabled' => $isEnabled,
                ],
            );
        }

        $this->logManualActivity($village->account, $village, 'Village settings saved from dashboard.');
        $this->resetVillageBuildPlanState();

        session()->flash('dashboard-banner', "{$village->name}: village settings were saved.");
    }

    /**
     * Parse and import accounts from the textarea content.
     */
    public function importAccounts(ImportBulkAccounts $importBulkAccounts, ImportDraftStore $draftStore): void
    {
        $this->validate([
            'bulkImportDraft' => ['required', 'string'],
        ]);

        try {
            $result = $importBulkAccounts->handle($this->bulkImportDraft);
        } catch (Throwable $throwable) {
            throw ValidationException::withMessages([
                'bulkImportDraft' => $throwable->getMessage(),
            ]);
        }

        $draftStore->put($this->bulkImportDraft);
        $this->showImportModal = false;

        $queuedLoginCount = $this->queueImportedAccountLogins($result['account_ids']);

        session()->flash(
            'dashboard-banner',
            "Accounts & Login updated {$result['imported']} new account(s), refreshed {$result['updated']}, archived {$result['archived']}, and queued {$queuedLoginCount} login/sync check(s).",
        );
    }

    /**
     * Toggle the global automation execution switch.
     */
    public function toggleAutomation(): void
    {
        if (! Schema::hasTable('system_settings')) {
            return;
        }

        $enabled = ! SystemSetting::automationEnabled();

        SystemSetting::setAutomationEnabled($enabled);

        session()->flash(
            'dashboard-banner',
            $enabled
                ? 'Global automation is now ON. Read and execution flows may continue.'
                : 'Global automation is now OFF. Queued Travian sync and execution flows will stop before sending external requests.',
        );
    }

    /**
     * Persist the global program settings.
     */
    public function saveProgramSettings(): void
    {
        if (! Schema::hasTable('system_settings')) {
            return;
        }

        $this->validate([
            'defaultUserAgent' => ['nullable', 'string', 'max:1000'],
            'globalFieldPriorityDraft.wood' => ['required', 'integer', 'min:1', 'max:4'],
            'globalFieldPriorityDraft.clay' => ['required', 'integer', 'min:1', 'max:4'],
            'globalFieldPriorityDraft.iron' => ['required', 'integer', 'min:1', 'max:4'],
            'globalFieldPriorityDraft.crop' => ['required', 'integer', 'min:1', 'max:4'],
            'globalPrioritizeCropFieldsWhenNegativeDraft' => ['boolean'],
            'globalFieldLevelCapDraft' => ['required', 'integer', 'min:1', 'max:20'],
            'globalTradeMaxDurationMinutesDraft' => ['required', 'integer', 'min:1', 'max:10080'],
            'globalHeroDefaultsDraft.adventures_enabled' => ['boolean'],
            'globalHeroDefaultsDraft.min_health' => ['required', 'integer', 'min:0', 'max:100'],
            'globalHeroDefaultsDraft.revive_enabled' => ['boolean'],
            'globalHeroDefaultsDraft.attribute_upgrade_enabled' => ['boolean'],
            'globalHeroDefaultsDraft.attribute_weights.power' => ['required', 'integer', 'min:0', 'max:100'],
            'globalHeroDefaultsDraft.attribute_weights.offBonus' => ['required', 'integer', 'min:0', 'max:100'],
            'globalHeroDefaultsDraft.attribute_weights.defBonus' => ['required', 'integer', 'min:0', 'max:100'],
            'globalHeroDefaultsDraft.attribute_weights.productionPoints' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        SystemSetting::setDefaultUserAgent($this->defaultUserAgent);
        SystemSetting::setConstructionDefaults([
            'field_priority' => $this->globalFieldPriorityDraft,
            'prioritize_crop_fields_when_negative' => $this->globalPrioritizeCropFieldsWhenNegativeDraft,
            'field_level_cap' => $this->globalFieldLevelCapDraft,
        ]);
        SystemSetting::setTradeDefaults([
            'max_duration_seconds' => $this->minutesToSeconds($this->globalTradeMaxDurationMinutesDraft),
        ]);
        SystemSetting::setHeroDefaults($this->globalHeroDefaultsDraft);
        $this->showProgramSettingsModal = false;
        $this->programSettingsTab = 'generals';

        session()->flash(
            'dashboard-banner',
            trim($this->defaultUserAgent) !== ''
                ? 'Program settings saved. Accounts without a custom user agent will now inherit the global fallback user agent.'
                : 'Program settings saved. Global construction defaults were also updated.',
        );
    }

    /**
     * Persist the edited account settings modal.
     */
    public function saveAccountSettings(): void
    {
        $this->validate([
            'editingAccountId' => ['required', 'integer', 'exists:accounts,id'],
            'accountInheritUserAgentDraft' => ['boolean'],
            'accountUserAgentDraft' => ['nullable', 'string', 'max:1000'],
            'accountAcceptQuestsDraft' => ['boolean'],
            'accountActiveProxyDraft' => ['required', 'string', 'max:40'],
            'accountProxyDrafts' => ['array'],
            'accountProxyDrafts.*.id' => ['nullable', 'integer'],
            'accountProxyDrafts.*.scheme' => ['required', 'string', 'in:http,https,socks4,socks4a,socks5,socks5h'],
            'accountProxyDrafts.*.host' => ['nullable', 'string', 'max:255'],
            'accountProxyDrafts.*.port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'accountProxyDrafts.*.username' => ['nullable', 'string', 'max:255'],
            'accountProxyDrafts.*.password' => ['nullable', 'string', 'max:1000'],
            'accountProxyDrafts.*.status' => ['required', 'string', 'in:active,disabled,cooldown'],
            'accountHeroUseGlobalSettingsDraft' => ['boolean'],
            'accountHeroAdventuresEnabledDraft' => ['boolean'],
            'accountHeroMinHealthDraft' => ['required', 'integer', 'min:0', 'max:100'],
            'accountHeroReviveEnabledDraft' => ['boolean'],
            'accountHeroAttributeUpgradeEnabledDraft' => ['boolean'],
            'accountHeroAttributeWeightsDraft.power' => ['required', 'integer', 'min:0', 'max:100'],
            'accountHeroAttributeWeightsDraft.offBonus' => ['required', 'integer', 'min:0', 'max:100'],
            'accountHeroAttributeWeightsDraft.defBonus' => ['required', 'integer', 'min:0', 'max:100'],
            'accountHeroAttributeWeightsDraft.productionPoints' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $account = Account::query()
            ->with(['settings', 'proxies'])
            ->findOrFail((int) $this->editingAccountId);
        $settings = $account->settings ?? $account->settings()->create([
            'resource_priorities' => AccountSetting::defaultResourcePriorities(),
        ]);

        $account->forceFill([
            'user_agent' => $this->accountInheritUserAgentDraft
                ? null
                : trim($this->accountUserAgentDraft),
        ])->save();

        $settings->forceFill([
            'accept_quests' => $this->accountAcceptQuestsDraft,
            'hero_use_global_settings' => $this->accountHeroUseGlobalSettingsDraft,
            'hero_adventures_enabled' => $this->accountHeroAdventuresEnabledDraft,
            'hero_min_health' => max(0, min(100, (int) $this->accountHeroMinHealthDraft)),
            'hero_revive_enabled' => $this->accountHeroReviveEnabledDraft,
            'hero_attribute_upgrade_enabled' => $this->accountHeroAttributeUpgradeEnabledDraft,
            'hero_attribute_weights' => $this->normalizeHeroAttributeWeights($this->accountHeroAttributeWeightsDraft),
        ])->save();

        $this->saveAccountProxyDrafts($account);

        $this->logManualActivity($account, null, 'Account settings saved from dashboard.');
        $this->resetAccountSettingsState();

        session()->flash('dashboard-banner', "Account {$account->username}: settings were saved.");
    }

    /**
     * Activate an account from the dashboard.
     */
    public function activateAccount(int $accountId): void
    {
        $account = Account::query()->findOrFail($accountId);

        $account->forceFill([
            'is_active' => true,
            'status' => AccountStatus::Active,
        ])->save();

        $this->logManualActivity($account, null, 'Account activated from dashboard.');
    }

    /**
     * Pause an account from the dashboard.
     */
    public function pauseAccount(int $accountId): void
    {
        $account = Account::query()->findOrFail($accountId);

        $account->forceFill([
            'is_active' => false,
            'status' => AccountStatus::Paused,
        ])->save();

        $this->logManualActivity($account, null, 'Account paused from dashboard.');
    }

    /**
     * Queue a manual sync marker for an account.
     */
    public function requestAccountSync(int $accountId): void
    {
        $account = Account::query()->findOrFail($accountId);

        if (! SystemSetting::automationEnabled()) {
            session()->flash('dashboard-banner', 'Program automation is paused. Resume it before requesting an account update.');

            return;
        }

        if (! $account->is_active || $account->is_archived) {
            session()->flash('dashboard-banner', "Account {$account->username} is paused. Activate it before requesting an update.");

            return;
        }

        $account->forceFill([
            'connection_retry_after' => null,
        ])->save();

        ActivityLog::query()->create([
            'account_id' => $account->id,
            'activity_type' => ActivityType::Sync,
            'status' => ActivityLogStatus::Pending,
            'message' => 'Sync requested and queued from dashboard.',
            'scheduled_at' => now(),
        ]);

        SyncTravianAccountJob::dispatch($account->id, null, true);

        $this->dashboardRevision = '';

        session()->flash('dashboard-banner', "Account {$account->username} was queued for background sync.");
    }

    /**
     * Queue login/sync checks for accounts touched by Accounts & Login.
     *
     * @param  list<int>  $accountIds
     */
    protected function queueImportedAccountLogins(array $accountIds): int
    {
        if ($accountIds === []) {
            return 0;
        }

        if (! SystemSetting::automationEnabled()) {
            return 0;
        }

        $queuedCount = 0;

        Account::query()
            ->whereKey($accountIds)
            ->where('is_archived', false)
            ->orderBy('id')
            ->get()
            ->each(function (Account $account) use (&$queuedCount): void {
                $account->forceFill([
                    'status' => AccountStatus::Syncing,
                    'connection_retry_after' => null,
                ])->save();

                ActivityLog::query()->create([
                    'account_id' => $account->id,
                    'activity_type' => ActivityType::Login,
                    'status' => ActivityLogStatus::Pending,
                    'message' => 'Login and account sync queued from Accounts & Login.',
                    'scheduled_at' => now(),
                ]);

                SyncTravianAccountJob::dispatch($account->id, null, true);
                $queuedCount++;
            });

        return $queuedCount;
    }

    /**
     * Toggle village active state.
     */
    public function toggleVillage(int $villageId): void
    {
        $village = Village::query()->findOrFail($villageId);

        $village->forceFill([
            'is_active' => ! $village->is_active,
        ])->save();

        $this->logManualActivity(
            $village->account,
            $village,
            $village->is_active ? 'Village activated from dashboard.' : 'Village paused from dashboard.',
        );
    }

    /**
     * Toggle automatic field upgrades for one village.
     */
    public function toggleVillageFieldsAutomation(int $villageId): void
    {
        $village = Village::query()->with(['account', 'settings'])->findOrFail($villageId);
        $settings = $village->settings ?? $village->settings()->create([
            'field_priority' => VillageSetting::defaultFieldPriority(),
        ]);
        $isPaused = (bool) $settings->pause_fields;

        $settings->forceFill([
            'pause_fields' => ! $isPaused,
        ])->save();

        $message = $isPaused
            ? 'Village field automation enabled from dashboard.'
            : 'Village field automation paused from dashboard.';

        $this->logManualActivity($village->account, $village, $message);
        session()->flash('dashboard-banner', "{$village->name}: ".($isPaused ? 'field upgrades are now ON.' : 'field upgrades are now OFF.'));
    }

    /**
     * Toggle automatic building upgrades for one village.
     */
    public function toggleVillageBuildingsAutomation(int $villageId): void
    {
        $village = Village::query()->with(['account', 'settings'])->findOrFail($villageId);
        $settings = $village->settings ?? $village->settings()->create([
            'field_priority' => VillageSetting::defaultFieldPriority(),
        ]);
        $isPaused = (bool) $settings->pause_buildings;

        $settings->forceFill([
            'pause_buildings' => ! $isPaused,
        ])->save();

        $message = $isPaused
            ? 'Village building automation enabled from dashboard.'
            : 'Village building automation paused from dashboard.';

        $this->logManualActivity($village->account, $village, $message);
        session()->flash('dashboard-banner', "{$village->name}: ".($isPaused ? 'building upgrades are now ON.' : 'building upgrades are now OFF.'));
    }

    /**
     * Toggle hero resource usage for construction shortages in one village.
     */
    public function toggleVillageHeroResources(int $villageId): void
    {
        $village = Village::query()->with(['account', 'settings'])->findOrFail($villageId);
        $settings = $village->settings ?? $village->settings()->create([
            'field_priority' => VillageSetting::defaultFieldPriority(),
        ]);
        $isEnabled = ! (bool) $settings->hero_resources_enabled;

        $settings->forceFill([
            'hero_resources_enabled' => $isEnabled,
        ])->save();

        $this->logManualActivity(
            $village->account,
            $village,
            'Village hero resource usage '.($isEnabled ? 'enabled' : 'paused').' from dashboard.',
        );

        session()->flash('dashboard-banner', "{$village->name}: hero resources are now ".($isEnabled ? 'ON.' : 'OFF.'));
    }

    /**
     * Toggle one field slot inside the village field automation list.
     */
    public function toggleVillageFieldSlotAutomation(int $villageId, int $slotId): void
    {
        if ($slotId < 1 || $slotId > 18) {
            return;
        }

        $village = Village::query()->with('account')->findOrFail($villageId);
        $slot = $village->buildings()
            ->where('slot_id', $slotId)
            ->whereBetween('building_gid', [1, 4])
            ->firstOrFail();
        $isEnabled = ! (bool) $slot->automation_enabled;

        $slot->forceFill([
            'automation_enabled' => $isEnabled,
        ])->save();

        $this->logManualActivity(
            $village->account,
            $village,
            "Field slot {$slotId} automation ".($isEnabled ? 'enabled' : 'paused').' from dashboard.',
        );
    }

    /**
     * Toggle one existing building slot and mirror the state to its layout target.
     */
    public function toggleVillageBuildingSlotAutomation(int $villageId, int $slotId): void
    {
        if ($slotId < 19 || $slotId > 40) {
            return;
        }

        $village = Village::query()->with('account')->findOrFail($villageId);
        $slot = $village->buildings()
            ->where('slot_id', $slotId)
            ->where('building_gid', '>', 0)
            ->firstOrFail();
        $isEnabled = ! (bool) $slot->automation_enabled;

        $slot->forceFill([
            'automation_enabled' => $isEnabled,
        ])->save();

        $village->buildingTargets()
            ->where('slot_id', $slotId)
            ->update([
                'is_enabled' => $isEnabled,
            ]);

        $this->logManualActivity(
            $village->account,
            $village,
            "Building slot {$slotId} automation ".($isEnabled ? 'enabled' : 'paused').' from dashboard.',
        );
    }

    /**
     * Toggle celebration automation for one village from the compact row button.
     */
    public function toggleVillageCelebrationAutomation(int $villageId): void
    {
        $village = Village::query()->with(['account', 'settings'])->findOrFail($villageId);
        $settings = $village->settings ?? $village->settings()->create([
            'field_priority' => VillageSetting::defaultFieldPriority(),
        ]);
        $isEnabled = ! (bool) $settings->celebration_enabled;

        $updates = [
            'celebration_enabled' => $isEnabled,
        ];

        if ($isEnabled && ! in_array($settings->celebration_type?->value, [VillageCelebrationType::Small->value, VillageCelebrationType::Great->value], true)) {
            $updates['celebration_type'] = VillageCelebrationType::Small;
        }

        $settings->forceFill($updates)->save();

        $this->logManualActivity(
            $village->account,
            $village,
            'Village celebration automation '.($isEnabled ? 'enabled' : 'paused').' from dashboard.',
        );
    }

    /**
     * Toggle troop training automation for one village from the compact row button.
     */
    public function toggleVillageTroopTrainingAutomation(int $villageId): void
    {
        $village = Village::query()->with(['account', 'settings'])->findOrFail($villageId);
        $settings = $village->settings ?? $village->settings()->create([
            'field_priority' => VillageSetting::defaultFieldPriority(),
        ]);
        $isEnabled = ! (bool) $settings->troop_training_enabled;

        $settings->forceFill([
            'troop_training_enabled' => $isEnabled,
        ])->save();

        $this->logManualActivity(
            $village->account,
            $village,
            'Village troop training automation '.($isEnabled ? 'enabled' : 'paused').' from dashboard.',
        );
    }

    /**
     * Move one schedule entry to the front of its queue, or remove that override.
     */
    public function toggleVillageSchedulePin(int $villageId, string $scheduleKey): void
    {
        if (! $this->isSupportedScheduleKey($scheduleKey)) {
            return;
        }

        $isPinned = false;

        $this->updateVillageConstructionSchedule($villageId, function (array &$schedule) use ($scheduleKey, &$isPinned): void {
            if (in_array($scheduleKey, $schedule['pinned'], true)) {
                $schedule['pinned'] = $this->withoutScheduleKey($schedule['pinned'], $scheduleKey);
                $isPinned = false;

                return;
            }

            $schedule['pinned'] = $this->withoutScheduleKey($schedule['pinned'], $scheduleKey);
            array_unshift($schedule['pinned'], $scheduleKey);
            $isPinned = true;
        }, function () use ($scheduleKey, &$isPinned): string {
            return "Schedule entry {$scheduleKey} ".($isPinned ? 'moved to the front' : 'removed from pinned schedule').' from dashboard.';
        });
    }

    /**
     * Toggle whether one schedule entry blocks later candidates until it can run.
     */
    public function toggleVillageScheduleHold(int $villageId, string $scheduleKey): void
    {
        if (! $this->isSupportedScheduleKey($scheduleKey)) {
            return;
        }

        $heldAfterToggle = false;

        $this->updateVillageConstructionSchedule($villageId, function (array &$schedule) use ($scheduleKey, &$heldAfterToggle): void {
            if (in_array($scheduleKey, $schedule['held'], true)) {
                $schedule['held'] = $this->withoutScheduleKey($schedule['held'], $scheduleKey);
                $heldAfterToggle = false;

                return;
            }

            $schedule['held'][] = $scheduleKey;
            $heldAfterToggle = true;
        }, function () use ($scheduleKey, &$heldAfterToggle): string {
            return "Schedule entry {$scheduleKey} ".($heldAfterToggle ? 'held' : 'released').' from dashboard.';
        });
    }

    /**
     * Queue a manual village sync marker.
     */
    public function requestVillageSync(int $villageId): void
    {
        $village = Village::query()->with('account')->findOrFail($villageId);

        if (! $this->queueVillageSync($village, 'Village-only update requested and queued.')) {
            session()->flash(
                'dashboard-banner',
                SystemSetting::automationEnabled()
                    ? "Village {$village->name} was not queued because its account or village is paused."
                    : 'Program automation is paused. Resume it before requesting a village update.',
            );

            return;
        }

        $this->dashboardRevision = '';

        session()->flash('dashboard-banner', "Village {$village->name} was queued for sync, then village automation.");
    }

    public function openMarketplaceTransferModal(int $villageId): void
    {
        $village = Village::query()
            ->with(['account.villages', 'buildings', 'resourceState', 'runtimeState', 'settings'])
            ->findOrFail($villageId);
        $settings = $village->settings ?? $village->settings()->create([
            'field_priority' => VillageSetting::defaultFieldPriority(),
        ]);

        $this->marketplaceSourceVillageId = $village->id;
        $this->marketplaceSourceVillageLabel = trim($village->name.' ['.$village->x.'|'.$village->y.']');
        $this->marketplaceTransferTab = 'send';
        $this->marketplaceDestinationMode = 'owned';
        $this->marketplaceDestinationVillageId = $village->account->villages
            ->where('id', '!=', $village->id)
            ->sortBy('name')
            ->first()?->id;
        $this->marketplaceDestinationX = '';
        $this->marketplaceDestinationY = '';
        $this->marketplaceWoodDraft = 0;
        $this->marketplaceClayDraft = 0;
        $this->marketplaceIronDraft = 0;
        $this->marketplaceCropDraft = 0;
        $this->marketplaceSnapshotRefreshPollUntil = null;
        $this->villageSendResourcesDraft = (bool) $settings->send_enabled;
        $this->villageSupplyResourcesDraft = (bool) $settings->support_enabled;
        $this->villageSupplyNegativeCropDraft = (bool) $settings->supply_negative_crop_enabled;
        $this->villageSendMinResourcePercentageDraft = max(0, min(100, (int) ($settings->send_min_resource_percentage ?? 30)));
        $this->villageSendReserveResourcePercentageDraft = max(0, min(100, (int) ($settings->send_reserve_resource_percentage ?? 10)));
        $this->villageTradeMaxDurationMinutesDraft = $this->secondsToWholeMinutes(
            (int) ($settings->trade_max_duration_seconds ?? VillageSetting::defaultTradeMaxDurationSeconds()),
        );

        $this->showMarketplaceTransferModal = true;
    }

    public function refreshMarketplaceSnapshot(): void
    {
        $village = $this->marketplaceSourceVillageId !== null
            ? Village::query()->with(['account', 'buildings', 'runtimeState'])->find($this->marketplaceSourceVillageId)
            : null;

        if (! $village instanceof Village || ! $village->account instanceof Account || ! $this->marketplaceCapacityCanRefresh($village)) {
            session()->flash('dashboard-banner', 'Marketplace snapshot could not be queued. Check automation, village status, and marketplace sync.');

            return;
        }

        RefreshVillageMarketplaceSnapshotJob::dispatch($village->account_id, $village->id);
        $this->marketplaceSnapshotRefreshPollUntil = now()->addSeconds(90)->getTimestamp();

        session()->flash('dashboard-banner', "{$village->name}: marketplace snapshot refresh queued. The TR panel will update when the result is saved.");
    }

    public function refreshMarketplaceTransferCapacityView(): void
    {
        if (! $this->showMarketplaceTransferModal || $this->marketplaceTransferTab !== 'send') {
            $this->skipRender();

            return;
        }

        if ($this->marketplaceSnapshotRefreshPollUntil === null || now()->getTimestamp() > $this->marketplaceSnapshotRefreshPollUntil) {
            $this->marketplaceSnapshotRefreshPollUntil = null;
            $this->skipRender();
        }
    }

    public function updatedMarketplaceWoodDraft(): void
    {
        $this->clampMarketplaceResourceDrafts('wood');
    }

    public function updatedMarketplaceClayDraft(): void
    {
        $this->clampMarketplaceResourceDrafts('clay');
    }

    public function updatedMarketplaceIronDraft(): void
    {
        $this->clampMarketplaceResourceDrafts('iron');
    }

    public function updatedMarketplaceCropDraft(): void
    {
        $this->clampMarketplaceResourceDrafts('crop');
    }

    public function adjustMarketplaceResourceDraft(string $resource, int $direction): void
    {
        $property = [
            'wood' => 'marketplaceWoodDraft',
            'clay' => 'marketplaceClayDraft',
            'iron' => 'marketplaceIronDraft',
            'crop' => 'marketplaceCropDraft',
        ][$resource] ?? null;

        if ($property === null) {
            return;
        }

        $capacity = $this->marketplaceTransferCapacity();
        $step = max(1, (int) ($capacity['merchant_capacity'] ?? $this->merchantCapacityForTribe(null)));
        $this->{$property} = max(0, (int) $this->{$property} + ($direction >= 0 ? $step : -$step));

        $this->clampMarketplaceResourceDrafts($resource);
    }

    public function setMarketplaceTransferTab(string $tab): void
    {
        if (! in_array($tab, ['send', 'settings'], true)) {
            return;
        }

        $this->marketplaceTransferTab = $tab;
    }

    public function closeMarketplaceTransferModal(): void
    {
        $this->showMarketplaceTransferModal = false;
        $this->marketplaceTransferTab = 'send';
        $this->marketplaceSourceVillageId = null;
        $this->marketplaceSourceVillageLabel = '';
        $this->marketplaceDestinationVillageId = null;
        $this->marketplaceDestinationMode = 'owned';
        $this->marketplaceDestinationX = '';
        $this->marketplaceDestinationY = '';
        $this->marketplaceWoodDraft = 0;
        $this->marketplaceClayDraft = 0;
        $this->marketplaceIronDraft = 0;
        $this->marketplaceCropDraft = 0;
        $this->marketplaceSnapshotRefreshPollUntil = null;
    }

    public function openVillageDemolitionModal(int $villageId): void
    {
        $village = Village::query()
            ->with(['account', 'buildings', 'runtimeState'])
            ->findOrFail($villageId);

        $this->demolitionVillageId = $village->id;
        $this->demolitionVillageLabel = trim($village->name.' ['.$village->x.'|'.$village->y.']');
        $this->demolitionSelectedSlotId = $this->defaultDemolitionSlotId($village);
        $this->showVillageDemolitionModal = true;
    }

    public function closeVillageDemolitionModal(): void
    {
        $this->showVillageDemolitionModal = false;
        $this->demolitionVillageId = null;
        $this->demolitionVillageLabel = '';
        $this->demolitionSelectedSlotId = null;
    }

    public function refreshVillageDemolitionSnapshot(): void
    {
        $village = $this->demolitionVillage();

        if (! $village instanceof Village || ! $village->account instanceof Account) {
            return;
        }

        RefreshVillageDemolitionSnapshotJob::dispatch($village->account_id, $village->id);

        session()->flash('dashboard-banner', "{$village->name}: demolition snapshot refresh queued. Wait for the activity result, then reopen or refresh the panel.");
    }

    public function queueVillageBuildingDemolition(): void
    {
        $village = $this->demolitionVillage();
        $slotId = (int) $this->demolitionSelectedSlotId;

        if (! $village instanceof Village || ! $village->account instanceof Account || $slotId < 19 || $slotId > 40) {
            session()->flash('dashboard-banner', 'Choose a building to demolish.');

            return;
        }

        $mainBuildingLevel = $this->localMainBuildingLevel($village);

        if ($mainBuildingLevel < 10) {
            session()->flash('dashboard-banner', "Cannot demolish yet: Main Building is level {$mainBuildingLevel}, and level 10 is required.");

            return;
        }

        DemolishVillageBuildingJob::dispatch($village->account_id, $village->id, $slotId);

        $this->closeVillageDemolitionModal();
        session()->flash('dashboard-banner', "{$village->name}: building demolition queued.");
    }

    public function queueCancelVillageDemolition(): void
    {
        $village = $this->demolitionVillage();
        $activeDemolition = $this->demolitionSnapshot()['active'] ?? null;
        $cancelUri = is_array($activeDemolition) ? (string) ($activeDemolition['cancel_uri'] ?? '') : '';

        if (! $village instanceof Village || ! $village->account instanceof Account || $cancelUri === '') {
            session()->flash('dashboard-banner', 'No active demolition cancel link is available yet.');

            return;
        }

        CancelVillageDemolitionJob::dispatch($village->account_id, $village->id, $cancelUri);

        $this->closeVillageDemolitionModal();
        session()->flash('dashboard-banner', "{$village->name}: demolition cancel queued.");
    }

    public function saveMarketplaceTradeSettings(): void
    {
        $this->validate([
            'marketplaceSourceVillageId' => ['required', 'integer', 'exists:villages,id'],
            'villageSendResourcesDraft' => ['boolean'],
            'villageSupplyResourcesDraft' => ['boolean'],
            'villageSupplyNegativeCropDraft' => ['boolean'],
            'villageSendMinResourcePercentageDraft' => ['required', 'integer', 'min:0', 'max:100'],
            'villageSendReserveResourcePercentageDraft' => ['required', 'integer', 'min:0', 'max:100'],
            'villageTradeMaxDurationMinutesDraft' => ['required', 'integer', 'min:1', 'max:10080'],
        ]);

        $village = Village::query()
            ->with(['account', 'settings'])
            ->findOrFail((int) $this->marketplaceSourceVillageId);

        $settings = $village->settings ?? $village->settings()->create([
            'field_priority' => VillageSetting::defaultFieldPriority(),
        ]);

        $settings->forceFill([
            'send_enabled' => $this->villageSendResourcesDraft,
            'support_enabled' => $this->villageSupplyResourcesDraft,
            'supply_negative_crop_enabled' => $this->villageSupplyResourcesDraft && $this->villageSupplyNegativeCropDraft,
            'send_min_resource_percentage' => max(0, min(100, (int) $this->villageSendMinResourcePercentageDraft)),
            'send_reserve_resource_percentage' => max(0, min(100, (int) $this->villageSendReserveResourcePercentageDraft)),
            'trade_max_duration_seconds' => $this->minutesToSeconds($this->villageTradeMaxDurationMinutesDraft),
        ])->save();

        $this->logManualActivity($village->account, $village, 'Village trade settings updated from TR panel.');
        $this->dashboardRevision = '';
        session()->flash('dashboard-banner', "{$village->name}: trade settings saved.");
    }

    public function queueManualMarketplaceTransfer(): void
    {
        $sourceVillage = $this->marketplaceSourceVillageId !== null
            ? Village::query()->with(['account', 'resourceState', 'runtimeState'])->find($this->marketplaceSourceVillageId)
            : null;

        if (! $sourceVillage instanceof Village || ! $sourceVillage->account instanceof Account) {
            return;
        }

        if ($this->marketplaceDestinationMode === 'owned') {
            $destinationVillage = $this->marketplaceDestinationVillageId !== null
                ? Village::query()
                    ->where('account_id', $sourceVillage->account_id)
                    ->find($this->marketplaceDestinationVillageId)
                : null;

            if (! $destinationVillage instanceof Village || $destinationVillage->x === null || $destinationVillage->y === null) {
                session()->flash('dashboard-banner', 'Choose a destination village with known coordinates.');

                return;
            }

            $x = (int) $destinationVillage->x;
            $y = (int) $destinationVillage->y;
        } else {
            $x = (int) $this->marketplaceDestinationX;
            $y = (int) $this->marketplaceDestinationY;
        }

        $resources = [
            'wood' => max(0, (int) $this->marketplaceWoodDraft),
            'clay' => max(0, (int) $this->marketplaceClayDraft),
            'iron' => max(0, (int) $this->marketplaceIronDraft),
            'crop' => max(0, (int) $this->marketplaceCropDraft),
        ];

        if (array_sum($resources) <= 0) {
            session()->flash('dashboard-banner', 'Enter at least one resource amount to send.');

            return;
        }

        $stockErrors = $this->marketplaceStockErrors($sourceVillage, $resources);

        if ($stockErrors !== []) {
            session()->flash('dashboard-banner', 'Cannot queue transfer: '.implode(', ', $stockErrors).'.');

            return;
        }

        $capacity = $this->marketplaceTransferCapacity($sourceVillage);
        $totalResources = array_sum($resources);

        if (($capacity['total_capacity'] ?? null) !== null && $totalResources > (int) $capacity['total_capacity']) {
            session()->flash(
                'dashboard-banner',
                "Cannot queue transfer: available merchants can carry {$capacity['total_capacity']} resources, but you entered {$totalResources}.",
            );

            return;
        }

        ActivityLog::query()->create([
            'account_id' => $sourceVillage->account_id,
            'village_id' => $sourceVillage->id,
            'activity_type' => ActivityType::Transfer,
            'status' => ActivityLogStatus::Pending,
            'payload' => [
                'destination' => ['x' => $x, 'y' => $y],
                'resources' => $resources,
            ],
            'message' => 'Manual marketplace transfer queued from dashboard.',
            'scheduled_at' => now(),
        ]);

        SendManualMarketplaceTransferJob::dispatch($sourceVillage->account_id, $sourceVillage->id, $x, $y, $resources);

        $this->closeMarketplaceTransferModal();
        $this->dashboardRevision = '';
        session()->flash('dashboard-banner', "Marketplace transfer from {$sourceVillage->name} was queued.");
    }

    /**
     * Queue one quiet village sync when a visible construction or movement timer elapsed.
     */
    public function queueVillageTimerSync(int $villageId): void
    {
        if (! SystemSetting::automationEnabled()) {
            $this->skipRender();

            return;
        }

        $village = Village::query()->with('account')->findOrFail($villageId);

        if (! $this->villageCanQueueSync($village)) {
            $this->skipRender();

            return;
        }

        if ($this->recentVillageSyncAlreadyQueued($village)) {
            $this->skipRender();

            return;
        }

        $this->queueVillageSync($village, 'Village timer elapsed; sync queued automatically.', true);

        $this->dashboardRevision = '';
        $this->skipRender();
    }

    protected function queueVillageSync(Village $village, string $message, bool $useReloadAuto = false): bool
    {
        if (! SystemSetting::automationEnabled()) {
            return false;
        }

        if (! $this->villageCanQueueSync($village)) {
            return false;
        }

        $village->account->forceFill([
            'status' => AccountStatus::Syncing,
            'connection_retry_after' => null,
        ])->save();

        ActivityLog::query()->create([
            'account_id' => $village->account->id,
            'village_id' => $village->id,
            'activity_type' => ActivityType::Sync,
            'status' => ActivityLogStatus::Pending,
            'message' => $message,
            'scheduled_at' => now(),
        ]);

        SyncTravianAccountJob::withChain([
            new RunTravianAutomationJob($village->account->id, $village->id, false, true),
        ])->dispatch($village->account->id, $village->id, true, $useReloadAuto);

        return true;
    }

    protected function villageCanQueueSync(Village $village): bool
    {
        $account = $village->account;

        return $account instanceof Account
            && $account->is_active
            && ! $account->is_archived
            && $village->is_active;
    }

    protected function recentVillageSyncAlreadyQueued(Village $village): bool
    {
        return ActivityLog::query()
            ->where('account_id', $village->account_id)
            ->where('village_id', $village->id)
            ->where('activity_type', ActivityType::Sync->value)
            ->whereIn('status', [ActivityLogStatus::Pending->value, ActivityLogStatus::Running->value])
            ->where('created_at', '>=', now()->subMinutes(2))
            ->exists();
    }

    /**
     * Render the dashboard component.
     */
    public function render(): View
    {
        if (! Schema::hasTable('accounts') || ! Schema::hasTable('activity_logs')) {
            return view('livewire.dashboard.index', [
                ...$this->emptyDashboardState(),
                'importPreviewRows' => $this->buildImportPreviewRows(),
            ]);
        }

        $accounts = $this->loadAccounts();
        $activityLogs = $this->loadActivityLogs();
        $this->dashboardRevision = $this->computeDashboardRevision();

        return view('livewire.dashboard.index', [
            'accounts' => $accounts,
            'activityLogs' => $activityLogs,
            'marketplaceTransferVillages' => $this->marketplaceTransferVillages(),
            'marketplaceTransferCapacity' => $this->marketplaceTransferCapacity(),
            'demolitionSnapshot' => $this->demolitionSnapshot(),
            'demolitionBuildings' => $this->demolitionBuildings(),
            'stats' => $this->buildStats($accounts),
            'importPreviewRows' => $this->buildImportPreviewRows(),
            ...$this->buildSystemSettingsViewData(),
        ]);
    }

    /**
     * Load the dashboard accounts with the relationships needed by the UI.
     *
     * @return Collection<int, Account>
     */
    protected function loadAccounts(): Collection
    {
        $query = Account::query();

        if (Schema::hasColumn('accounts', 'is_archived')) {
            $query->where('is_archived', false);
        }

        if (Schema::hasColumn('accounts', 'import_position')) {
            $query
                ->orderByRaw('case when import_position > 0 then 0 else 1 end')
                ->orderBy('import_position');
        }

        return $query
            ->with([
                'settings',
                'proxies',
                'activeProxy',
                'heroState',
                'latestTravianActivityLog',
                'villages.settings',
                'villages.resourceState',
                'villages.runtimeState',
                'villages.buildings' => fn ($query) => $query->orderBy('slot_id'),
                'villages.buildingTargets' => fn ($query) => $query->orderBy('priority')->orderBy('slot_id'),
            ])
            ->withCount('villages')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return Collection<int, Village>
     */
    protected function marketplaceTransferVillages(): Collection
    {
        if (! $this->showMarketplaceTransferModal || $this->marketplaceSourceVillageId === null) {
            return collect();
        }

        $sourceVillage = Village::query()->find($this->marketplaceSourceVillageId);

        if (! $sourceVillage instanceof Village) {
            return collect();
        }

        return Village::query()
            ->where('account_id', $sourceVillage->account_id)
            ->where('id', '!=', $sourceVillage->id)
            ->whereNotNull('x')
            ->whereNotNull('y')
            ->orderBy('name')
            ->get();
    }

    protected function demolitionVillage(): ?Village
    {
        return $this->demolitionVillageId !== null
            ? Village::query()->with(['account', 'buildings', 'runtimeState'])->find($this->demolitionVillageId)
            : null;
    }

    /**
     * @return array<string, mixed>
     */
    protected function demolitionSnapshot(): array
    {
        if (! $this->showVillageDemolitionModal) {
            return [];
        }

        $village = $this->demolitionVillage();
        $snapshot = $village?->runtimeState?->demolition_entries;

        return is_array($snapshot) ? $snapshot : [
            'main_building_level' => $village instanceof Village ? $this->localMainBuildingLevel($village) : null,
            'available_buildings' => [],
            'active' => null,
            'recorded_at' => null,
        ];
    }

    /**
     * @return Collection<int, array{slot_id:int,name:string,level:int}>
     */
    protected function demolitionBuildings(): Collection
    {
        if (! $this->showVillageDemolitionModal) {
            return collect();
        }

        $snapshotBuildings = collect($this->demolitionSnapshot()['available_buildings'] ?? [])
            ->filter(static fn (mixed $building): bool => is_array($building))
            ->map(static fn (array $building): array => [
                'slot_id' => (int) ($building['slot_id'] ?? 0),
                'name' => (string) ($building['name'] ?? 'Building'),
                'level' => (int) ($building['level'] ?? 0),
            ])
            ->filter(static fn (array $building): bool => $building['slot_id'] >= 19 && $building['slot_id'] <= 40 && $building['level'] > 0)
            ->values();

        if ($snapshotBuildings->isNotEmpty()) {
            return $snapshotBuildings;
        }

        $village = $this->demolitionVillage();

        if (! $village instanceof Village) {
            return collect();
        }

        return $village->buildings
            ->filter(static fn (VillageBuilding $building): bool => (int) $building->slot_id >= 19
                && (int) $building->slot_id <= 40
                && (int) $building->building_gid > 0
                && (int) $building->current_level > 0)
            ->sortBy('slot_id')
            ->map(static fn (VillageBuilding $building): array => [
                'slot_id' => (int) $building->slot_id,
                'name' => $building->building_type ?: (TravianBuildingCatalog::nameForGid((int) $building->building_gid) ?? 'Building'),
                'level' => (int) $building->current_level,
            ])
            ->values();
    }

    protected function defaultDemolitionSlotId(Village $village): ?int
    {
        $firstBuilding = $village->buildings
            ->filter(static fn (VillageBuilding $building): bool => (int) $building->slot_id >= 19
                && (int) $building->slot_id <= 40
                && (int) $building->building_gid > 0
                && (int) $building->current_level > 0
                && (int) $building->building_gid !== 15)
            ->sortBy('slot_id')
            ->first();

        return $firstBuilding instanceof VillageBuilding ? (int) $firstBuilding->slot_id : null;
    }

    protected function localMainBuildingLevel(Village $village): int
    {
        $mainBuilding = $village->buildings
            ->first(static fn (VillageBuilding $building): bool => (int) $building->building_gid === 15);

        return $mainBuilding instanceof VillageBuilding ? max(0, (int) $mainBuilding->current_level) : 0;
    }

    /**
     * Build editable proxy rows for the account settings modal.
     *
     * @return list<array{id:int|null, scheme:string, host:string, port:string, username:string, password:string, status:string, failure_count:int, lifetime_failure_count:int, cooldown_until:string|null, last_error_message:string|null}>
     */
    protected function buildAccountProxyDrafts(Account $account): array
    {
        return $account->proxies
            ->map(static fn (AccountProxy $proxy): array => [
                'id' => $proxy->id,
                'scheme' => $proxy->scheme,
                'host' => $proxy->host,
                'port' => (string) $proxy->port,
                'username' => (string) ($proxy->username ?? ''),
                'password' => '',
                'status' => $proxy->status,
                'failure_count' => (int) $proxy->failure_count,
                'lifetime_failure_count' => (int) $proxy->lifetime_failure_count,
                'cooldown_until' => $proxy->cooldown_until?->toIso8601String(),
                'last_error_message' => $proxy->last_error_message,
            ])
            ->values()
            ->all();
    }

    /**
     * Resolve the selected radio value for the account proxy modal.
     */
    protected function resolveAccountActiveProxyDraft(Account $account): string
    {
        $proxyIds = $account->proxies->pluck('id')->map(static fn (mixed $id): int => (int) $id)->all();
        $activeProxyId = $account->active_account_proxy_id !== null ? (int) $account->active_account_proxy_id : null;

        if ($activeProxyId !== null && in_array($activeProxyId, $proxyIds, true)) {
            return 'proxy:'.$activeProxyId;
        }

        if ($account->proxy_ip !== null && $account->proxy_port !== null) {
            $matchingProxy = $account->proxies->first(
                fn (AccountProxy $proxy): bool => $proxy->host === $account->proxy_ip
                    && (int) $proxy->port === (int) $account->proxy_port,
            );

            if ($matchingProxy instanceof AccountProxy) {
                return 'proxy:'.$matchingProxy->id;
            }
        }

        return 'direct';
    }

    /**
     * Move pre-pool proxy columns into a real pool row once.
     */
    protected function ensureLegacyAccountProxyIsPooled(Account $account): void
    {
        if ($account->proxies->isNotEmpty() || $account->proxy_ip === null || $account->proxy_port === null) {
            return;
        }

        $proxy = $account->proxies()->create([
            'scheme' => $this->normalizeProxyScheme((string) ($account->proxy_scheme ?: 'http')),
            'host' => (string) $account->proxy_ip,
            'port' => (int) $account->proxy_port,
            'username' => $account->proxy_username,
            'password' => $account->proxy_password,
            'status' => AccountProxy::StatusActive,
            'position' => 1,
        ]);

        $account->forceFill([
            'active_account_proxy_id' => $proxy->id,
        ])->save();
    }

    /**
     * Persist editable proxy rows and mirror the selected one onto the account.
     */
    protected function saveAccountProxyDrafts(Account $account): void
    {
        $existingProxies = $account->proxies()->get()->keyBy('id');
        $keptProxyIds = [];
        $selectedProxy = null;

        foreach (array_values($this->accountProxyDrafts) as $position => $draft) {
            $host = trim((string) ($draft['host'] ?? ''));
            $port = (int) ($draft['port'] ?? 0);

            if ($host === '' || $port < 1) {
                continue;
            }

            $proxy = isset($draft['id']) && $draft['id'] !== null
                ? $existingProxies->get((int) $draft['id'])
                : new AccountProxy(['account_id' => $account->id]);

            if (! $proxy instanceof AccountProxy) {
                $proxy = new AccountProxy(['account_id' => $account->id]);
            }

            $password = trim((string) ($draft['password'] ?? ''));
            $status = in_array($draft['status'] ?? AccountProxy::StatusActive, [AccountProxy::StatusActive, AccountProxy::StatusDisabled, AccountProxy::StatusCooldown], true)
                ? (string) $draft['status']
                : AccountProxy::StatusActive;

            $proxy->forceFill([
                'account_id' => $account->id,
                'scheme' => $this->normalizeProxyScheme((string) ($draft['scheme'] ?? 'http')),
                'host' => $host,
                'port' => $port,
                'username' => trim((string) ($draft['username'] ?? '')) ?: null,
                'status' => $status,
                'position' => $position + 1,
            ]);

            if ($password !== '' || ! $proxy->exists) {
                $proxy->password = $password !== '' ? $password : null;
            }

            if ($status === AccountProxy::StatusActive) {
                $proxy->cooldown_until = null;
                $proxy->failure_count = 0;
                $proxy->last_error_message = null;
            }

            $proxy->save();
            $keptProxyIds[] = (int) $proxy->id;

            if ($this->accountActiveProxyDraft === 'proxy:'.$proxy->id || $this->accountActiveProxyDraft === 'new:'.$position) {
                $selectedProxy = $proxy;
            }
        }

        $account->proxies()
            ->whereNotIn('id', $keptProxyIds === [] ? [0] : $keptProxyIds)
            ->delete();

        if (! $selectedProxy instanceof AccountProxy || $selectedProxy->status !== AccountProxy::StatusActive) {
            $selectedProxy = $account->proxies()
                ->where('status', AccountProxy::StatusActive)
                ->orderBy('position')
                ->orderBy('id')
                ->first();
        }

        if ($this->accountActiveProxyDraft === 'direct' || ! $selectedProxy instanceof AccountProxy) {
            $account->forceFill([
                'active_account_proxy_id' => null,
                'proxy_scheme' => 'http',
                'proxy_ip' => null,
                'proxy_port' => null,
                'proxy_username' => null,
                'proxy_password' => null,
                'session_cookies' => null,
                'session_transport_fingerprint' => null,
            ])->save();

            return;
        }

        $account->forceFill([
            'active_account_proxy_id' => $selectedProxy->id,
            'proxy_scheme' => $selectedProxy->scheme,
            'proxy_ip' => $selectedProxy->host,
            'proxy_port' => $selectedProxy->port,
            'proxy_username' => $selectedProxy->username,
            'proxy_password' => $selectedProxy->password,
            'session_cookies' => null,
            'session_transport_fingerprint' => null,
        ])->save();
    }

    protected function normalizeProxyScheme(string $scheme): string
    {
        return in_array($scheme, ['http', 'https', 'socks4', 'socks4a', 'socks5', 'socks5h'], true)
            ? $scheme
            : 'http';
    }

    /**
     * Load the most recent activity log rows for the footer timeline.
     *
     * @return Collection<int, ActivityLog>
     */
    protected function loadActivityLogs(): Collection
    {
        return ActivityLog::query()
            ->with(['account', 'village'])
            ->latest()
            ->limit(50)
            ->get();
    }

    /**
     * Build a per-line visual preview for Accounts & Login input.
     *
     * @return list<array{line:int, valid:bool, server:string, username:string, password:string, proxy:string, user_agent:string, error:string|null}>
     */
    protected function buildImportPreviewRows(): array
    {
        $rows = [];
        $parser = app(BulkAccountImportParser::class);

        foreach (preg_split('/\R/u', $this->bulkImportDraft) ?: [] as $lineIndex => $line) {
            $trimmedLine = trim($line);

            if ($trimmedLine === '') {
                continue;
            }

            try {
                $record = $parser->parsePreviewLine($trimmedLine, $lineIndex + 1);
                $rows[] = [
                    'line' => $lineIndex + 1,
                    'valid' => true,
                    'server' => $record->serverUrl,
                    'username' => $record->username,
                    'password' => str_repeat('•', max(4, min(10, mb_strlen($record->password)))),
                    'proxy' => $record->proxyIp !== null && $record->proxyPort !== null
                        ? "{$record->proxyScheme}://{$record->proxyIp}:{$record->proxyPort}"
                        : 'Direct',
                    'user_agent' => $record->userAgent ?? 'Default UA',
                    'error' => null,
                ];
            } catch (Throwable $throwable) {
                $rows[] = [
                    'line' => $lineIndex + 1,
                    'valid' => false,
                    'server' => trim($trimmedLine, '|'),
                    'username' => '',
                    'password' => '',
                    'proxy' => '',
                    'user_agent' => '',
                    'error' => $throwable->getMessage(),
                ];
            }
        }

        return $rows;
    }

    /**
     * Build the top-level dashboard statistics.
     *
     * @param  Collection<int, Account>  $accounts
     * @return array<string, int>
     */
    protected function buildStats(Collection $accounts): array
    {
        return [
            'accounts' => $accounts->count(),
            'activeAccounts' => $accounts->where('is_active', true)->count(),
            'villages' => $accounts->sum('villages_count'),
            'syncing' => $accounts->where('status', AccountStatus::Syncing)->count(),
        ];
    }

    /**
     * Build the empty-state payload used before migrations are available.
     *
     * @return array{
     *     accounts: Collection<int, Account>,
     *     activityLogs: Collection<int, ActivityLog>,
     *     stats: array<string, int>
     * }
     */
    protected function emptyDashboardState(): array
    {
        return [
            'accounts' => collect(),
            'activityLogs' => collect(),
            'marketplaceTransferVillages' => collect(),
            'marketplaceTransferCapacity' => [
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
            ],
            'demolitionSnapshot' => [],
            'demolitionBuildings' => collect(),
            'stats' => [
                'accounts' => 0,
                'activeAccounts' => 0,
                'villages' => 0,
                'syncing' => 0,
            ],
            ...$this->buildSystemSettingsViewData(),
        ];
    }

    /**
     * Build the system settings view payload used by the dashboard.
     *
     * @return array{
     *     automationEnabled: bool,
     *     globalDefaultUserAgent: ?string,
     *     globalFieldPriority: array{wood: int, clay: int, iron: int, crop: int},
     *     globalFieldLevelCap: int,
     *     globalPrioritizeCropFieldsWhenNegative: bool,
     *     globalTradeDefaults: array{max_duration_seconds: int},
     *     globalHeroDefaults: array{
     *         adventures_enabled: bool,
     *         min_health: int,
     *         revive_enabled: bool,
     *         attribute_upgrade_enabled: bool,
     *         attribute_weights: array{power: int, offBonus: int, defBonus: int, productionPoints: int}
     *     }
     * }
     */
    protected function buildSystemSettingsViewData(): array
    {
        $settings = Schema::hasTable('system_settings')
            ? SystemSetting::dashboardSnapshot()
            : [
                'automation_enabled' => true,
                'default_user_agent' => null,
                'construction_defaults' => [
                    'field_priority' => SystemSetting::defaultFieldPriority(),
                    'prioritize_crop_fields_when_negative' => true,
                    'field_level_cap' => SystemSetting::defaultFieldLevelCap(),
                ],
                'trade_defaults' => SystemSetting::tradeDefaults(),
                'hero_defaults' => SystemSetting::heroDefaults(),
            ];

        return [
            'automationEnabled' => (bool) $settings['automation_enabled'],
            'globalDefaultUserAgent' => $settings['default_user_agent'],
            'globalFieldPriority' => $settings['construction_defaults']['field_priority'],
            'globalFieldLevelCap' => (int) ($settings['construction_defaults']['field_level_cap'] ?? SystemSetting::defaultFieldLevelCap()),
            'globalPrioritizeCropFieldsWhenNegative' => (bool) $settings['construction_defaults']['prioritize_crop_fields_when_negative'],
            'globalTradeDefaults' => $settings['trade_defaults'],
            'globalHeroDefaults' => $settings['hero_defaults'],
        ];
    }

    /**
     * Build the marketplace capacity snapshot for the open TR modal.
     *
     * @return array{available_merchants:int|null, merchant_capacity:int, total_capacity:int|null, resources: array{wood:int|null, clay:int|null, iron:int|null, crop:int|null}, reported_at: string|null}
     */
    protected function marketplaceTransferCapacity(?Village $sourceVillage = null): array
    {
        $sourceVillage ??= $this->marketplaceSourceVillageId !== null
            ? Village::query()->with(['resourceState', 'runtimeState'])->find($this->marketplaceSourceVillageId)
            : null;

        $fallbackCapacity = $this->merchantCapacityForTribe(
            $sourceVillage instanceof Village && $sourceVillage->runtimeState?->tribe_id !== null
                ? (int) $sourceVillage->runtimeState->tribe_id
                : null,
        );

        if (! $sourceVillage instanceof Village || ! $sourceVillage->resourceState instanceof VillageResourceState) {
            return [
                'available_merchants' => null,
                'merchant_capacity' => $fallbackCapacity,
                'total_capacity' => null,
                'resources' => [
                    'wood' => null,
                    'clay' => null,
                    'iron' => null,
                    'crop' => null,
                ],
                'reported_at' => null,
            ];
        }

        $availableMerchants = $sourceVillage->resourceState->available_merchants !== null
            ? max(0, (int) $sourceVillage->resourceState->available_merchants)
            : null;
        $merchantCapacity = $sourceVillage->resourceState->merchant_capacity !== null
            ? max(1, (int) $sourceVillage->resourceState->merchant_capacity)
            : $fallbackCapacity;

        return [
            'available_merchants' => $availableMerchants,
            'merchant_capacity' => $merchantCapacity,
            'total_capacity' => $availableMerchants !== null ? $availableMerchants * $merchantCapacity : null,
            'resources' => [
                'wood' => max(0, (int) $sourceVillage->resourceState->wood),
                'clay' => max(0, (int) $sourceVillage->resourceState->clay),
                'iron' => max(0, (int) $sourceVillage->resourceState->iron),
                'crop' => max(0, (int) $sourceVillage->resourceState->crop),
            ],
            'reported_at' => $sourceVillage->resourceState->server_reported_at?->diffForHumans(),
        ];
    }

    protected function marketplaceCapacityCanRefresh(Village $village): bool
    {
        if (! SystemSetting::automationEnabled() || ! $village->is_active || ! $village->account?->is_active) {
            return false;
        }

        $marketSlot = $village->buildings
            ->first(static fn (VillageBuilding $building): bool => (int) $building->building_gid === 17);

        if (! $marketSlot instanceof VillageBuilding) {
            return false;
        }

        return true;
    }

    /**
     * @param  array{wood:int, clay:int, iron:int, crop:int}  $resources
     * @return list<string>
     */
    protected function marketplaceStockErrors(Village $sourceVillage, array $resources): array
    {
        if (! $sourceVillage->resourceState instanceof VillageResourceState) {
            return [];
        }

        $labels = [
            'wood' => 'Wood',
            'clay' => 'Clay',
            'iron' => 'Iron',
            'crop' => 'Crop',
        ];
        $errors = [];

        foreach ($labels as $resource => $label) {
            $requested = max(0, (int) ($resources[$resource] ?? 0));
            $available = max(0, (int) $sourceVillage->resourceState->{$resource});

            if ($requested > $available) {
                $errors[] = "{$label} {$requested} exceeds current stock {$available}";
            }
        }

        return $errors;
    }

    protected function clampMarketplaceResourceDrafts(string $changedResource): void
    {
        $draftPropertyByResource = [
            'wood' => 'marketplaceWoodDraft',
            'clay' => 'marketplaceClayDraft',
            'iron' => 'marketplaceIronDraft',
            'crop' => 'marketplaceCropDraft',
        ];

        foreach ($draftPropertyByResource as $property) {
            $this->{$property} = max(0, (int) $this->{$property});
        }

        $capacity = $this->marketplaceTransferCapacity();
        $stockByResource = $capacity['resources'] ?? [];

        foreach ($draftPropertyByResource as $resource => $property) {
            if (($stockByResource[$resource] ?? null) === null) {
                continue;
            }

            $this->{$property} = min((int) $this->{$property}, max(0, (int) $stockByResource[$resource]));
        }

        $totalCapacity = $capacity['total_capacity'] ?? null;

        if ($totalCapacity === null || ! isset($draftPropertyByResource[$changedResource])) {
            return;
        }

        $totalResources = array_sum(array_map(fn (string $property): int => (int) $this->{$property}, $draftPropertyByResource));

        if ($totalResources <= (int) $totalCapacity) {
            return;
        }

        $changedProperty = $draftPropertyByResource[$changedResource];
        $overflow = $totalResources - (int) $totalCapacity;
        $this->{$changedProperty} = max(0, (int) $this->{$changedProperty} - $overflow);
    }

    protected function merchantCapacityForTribe(?int $tribeId): int
    {
        $capacity = (array) config('travian.game.merchant_capacity', []);

        return match ($tribeId) {
            1 => (int) ($capacity['roman'] ?? 500),
            2 => (int) ($capacity['teuton'] ?? 1000),
            3 => (int) ($capacity['gaul'] ?? 750),
            default => (int) ($capacity['roman'] ?? 500),
        };
    }

    protected function secondsToWholeMinutes(int $seconds): int
    {
        return max(1, (int) ceil(max(60, $seconds) / 60));
    }

    protected function minutesToSeconds(int $minutes): int
    {
        return max(1, min(10080, $minutes)) * 60;
    }

    protected function clampBuildingTargetLevel(int $buildingGid, int $targetLevel): int
    {
        $maxLevel = TravianBuildingCatalog::maxLevelForGid($buildingGid);

        if ($maxLevel !== null) {
            return max(0, min($targetLevel, (int) $maxLevel));
        }

        return max(0, $targetLevel);
    }

    public function formatTradeDurationMinutes(int $minutes): string
    {
        $minutes = max(1, $minutes);
        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;

        if ($hours <= 0) {
            return "{$remainingMinutes}m";
        }

        if ($remainingMinutes <= 0) {
            return "{$hours}h";
        }

        return "{$hours}h {$remainingMinutes}m";
    }

    protected function clampVillageFieldLevelCap(Village $village, int $fieldLevelCap): int
    {
        $maximum = $village->is_capital ? 20 : 10;

        return max(1, min($maximum, $fieldLevelCap));
    }

    /**
     * Build a cheap revision fingerprint from local tables that affect the dashboard.
     */
    protected function computeDashboardRevision(): string
    {
        if (! Schema::hasTable('accounts')) {
            return 'empty';
        }

        $tables = [
            'accounts' => 'updated_at',
            'account_proxies' => 'updated_at',
            'account_hero_states' => 'updated_at',
            'villages' => 'updated_at',
            'village_settings' => 'updated_at',
            'village_resource_states' => 'updated_at',
            'village_runtime_states' => 'updated_at',
            'village_buildings' => 'updated_at',
            'village_building_targets' => 'updated_at',
        ];

        $parts = [];

        foreach ($tables as $table => $column) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }

            $parts[$table] = DB::table($table)->max($column);
        }

        if (Schema::hasTable('activity_logs')) {
            $parts['activity_logs'] = DB::table('activity_logs')->max('id');
        }

        return sha1(json_encode($parts, JSON_THROW_ON_ERROR));
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

    /**
     * Update the saved construction schedule preferences for one village.
     */
    protected function updateVillageConstructionSchedule(int $villageId, callable $callback, callable $messageResolver): void
    {
        $village = Village::query()->with(['account', 'settings'])->findOrFail($villageId);
        $settings = $village->settings ?? $village->settings()->create([
            'field_priority' => VillageSetting::defaultFieldPriority(),
        ]);
        $schedule = $this->normalizeConstructionSchedule($settings->construction_schedule);

        $callback($schedule);

        $settings->forceFill([
            'construction_schedule' => $schedule,
        ])->save();

        $this->logManualActivity($village->account, $village, $messageResolver());
    }

    /**
     * @return array{pinned: list<string>, held: list<string>}
     */
    protected function normalizeConstructionSchedule(mixed $schedule): array
    {
        if (! is_array($schedule)) {
            return [
                'pinned' => [],
                'held' => [],
            ];
        }

        return [
            'pinned' => $this->normalizeScheduleKeyList($schedule['pinned'] ?? []),
            'held' => $this->normalizeScheduleKeyList($schedule['held'] ?? []),
        ];
    }

    /**
     * @return list<string>
     */
    protected function normalizeScheduleKeyList(mixed $scheduleKeys): array
    {
        if (! is_array($scheduleKeys)) {
            return [];
        }

        return array_values(array_unique(array_filter(
            array_map(static fn (mixed $scheduleKey): string => is_scalar($scheduleKey) ? (string) $scheduleKey : '', $scheduleKeys),
            fn (string $scheduleKey): bool => $this->isSupportedScheduleKey($scheduleKey),
        )));
    }

    /**
     * @param  list<string>  $scheduleKeys
     * @return list<string>
     */
    protected function withoutScheduleKey(array $scheduleKeys, string $scheduleKey): array
    {
        return array_values(array_filter(
            $scheduleKeys,
            static fn (string $existingScheduleKey): bool => $existingScheduleKey !== $scheduleKey,
        ));
    }

    /**
     * Validate dashboard schedule keys formatted as "field:slot:target" or "building:slot:target".
     */
    protected function isSupportedScheduleKey(string $scheduleKey): bool
    {
        $parts = explode(':', $scheduleKey);

        if (count($parts) !== 3) {
            return false;
        }

        [$queueKind, $slotId, $targetLevel] = $parts;

        if (! in_array($queueKind, ['field', 'building'], true) || ! ctype_digit($slotId) || ! ctype_digit($targetLevel)) {
            return false;
        }

        $slotId = (int) $slotId;
        $targetLevel = (int) $targetLevel;

        return $targetLevel >= 1
            && $targetLevel <= 20
            && (
                ($queueKind === 'field' && $slotId >= 1 && $slotId <= 18)
                || ($queueKind === 'building' && $slotId >= 19 && $slotId <= 40)
            );
    }

    /**
     * Normalize the editable field priority payload.
     *
     * @param  array<string, mixed>|null  $fieldPriority
     * @return array{wood: int, clay: int, iron: int, crop: int}
     */
    protected function normalizeFieldPriorityDraft(?array $fieldPriority): array
    {
        $defaults = VillageSetting::defaultFieldPriority();

        if (! is_array($fieldPriority)) {
            return $defaults;
        }

        return [
            'wood' => (int) ($fieldPriority['wood'] ?? $defaults['wood']),
            'clay' => (int) ($fieldPriority['clay'] ?? $defaults['clay']),
            'iron' => (int) ($fieldPriority['iron'] ?? $defaults['iron']),
            'crop' => (int) ($fieldPriority['crop'] ?? $defaults['crop']),
        ];
    }

    /**
     * Build the editable dorf2 slot draft for the current village.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function buildVillagePlanDraft(Village $village, ?int $tribeId): array
    {
        $currentSlots = $village->buildings->keyBy('slot_id');
        $targets = $village->buildingTargets->keyBy('slot_id');
        $draft = [];

        foreach (range(19, 40) as $slotId) {
            $currentSlot = $currentSlots->get($slotId);
            $target = $targets->get($slotId);
            $currentGid = $currentSlot instanceof VillageBuilding ? (int) $currentSlot->building_gid : 0;
            $currentLevel = $currentSlot instanceof VillageBuilding ? (int) $currentSlot->current_level : 0;
            $fixedSlotGid = TravianBuildingCatalog::fixedSlotGidForSlot($slotId, $tribeId);
            $targetGid = $target?->building_gid;

            if ($targetGid === null || (int) $targetGid === 0) {
                $targetGid = $target !== null
                    ? TravianBuildingCatalog::gidForName($target->building_type)
                    : null;
            }

            $resolvedBuildingGid = $targetGid;

            if ($resolvedBuildingGid === null || (int) $resolvedBuildingGid === 0) {
                $resolvedBuildingGid = $currentGid !== 0
                    ? $currentGid
                    : $fixedSlotGid;
            }

            $draft[$slotId] = [
                'slot_id' => $slotId,
                'current_gid' => $currentGid,
                'current_name' => $this->resolveCurrentSlotLabel($currentSlot, $slotId, $tribeId),
                'current_level' => $currentLevel,
                'building_gid' => (int) ($resolvedBuildingGid ?? 0),
                'target_level' => (int) ($target?->target_level ?? 0),
                'priority' => max(1, min(4, (int) ($target?->priority ?? 4))),
                'is_enabled' => (bool) ($target?->is_enabled ?? true),
                'is_locked' => $currentGid !== 0 || $fixedSlotGid !== null,
            ];
        }

        return $draft;
    }

    /**
     * Build the per-slot selectable building options for the current village.
     *
     * @return array<int, list<array{gid: int, label: string, category: int|null}>>
     */
    protected function buildSlotBuildingOptions(Village $village, ?int $tribeId): array
    {
        $currentSlots = $village->buildings->keyBy('slot_id');
        $genericOptions = collect(TravianBuildingCatalog::buildingOptionsForTribe($tribeId))->keyBy('gid');
        $slotOptions = [];

        foreach (range(19, 40) as $slotId) {
            $currentSlot = $currentSlots->get($slotId);
            $currentGid = $currentSlot instanceof VillageBuilding ? (int) $currentSlot->building_gid : 0;
            $fixedSlotGid = TravianBuildingCatalog::fixedSlotGidForSlot($slotId, $tribeId);

            if ($currentGid !== 0) {
                $option = $this->catalogOptionForGid($currentGid);
                $slotOptions[$slotId] = $option !== null ? [$option] : [];

                continue;
            }

            if ($fixedSlotGid !== null) {
                $option = $this->catalogOptionForGid($fixedSlotGid);
                $slotOptions[$slotId] = $option !== null ? [$option] : [];

                continue;
            }

            $slotOptions[$slotId] = array_values($genericOptions->all());
        }

        return $slotOptions;
    }

    /**
     * Resolve one select option payload for a gid.
     *
     * @return array{gid: int, label: string, category: int|null}|null
     */
    protected function catalogOptionForGid(int $gid): ?array
    {
        $label = TravianBuildingCatalog::nameForGid($gid);

        if ($label === null) {
            return null;
        }

        return [
            'gid' => $gid,
            'label' => $label,
            'category' => TravianBuildingCatalog::buildCategoryForGid($gid),
        ];
    }

    /**
     * Resolve the current slot label for the village plan modal.
     */
    protected function resolveCurrentSlotLabel(?VillageBuilding $currentSlot, int $slotId, ?int $tribeId): string
    {
        if ($currentSlot instanceof VillageBuilding && (int) $currentSlot->building_gid !== 0) {
            return $currentSlot->building_type ?: (TravianBuildingCatalog::nameForGid((int) $currentSlot->building_gid) ?? 'Occupied slot');
        }

        $fixedSlotGid = TravianBuildingCatalog::fixedSlotGidForSlot($slotId, $tribeId);

        if ($fixedSlotGid !== null) {
            return TravianBuildingCatalog::nameForGid($fixedSlotGid) ?? 'Fixed slot';
        }

        return 'Empty slot';
    }

    /**
     * Reload the edited village and refresh the celebration readiness warning.
     */
    protected function refreshVillageCelebrationReadinessMessage(): void
    {
        if ($this->editingVillageId === null) {
            $this->villageCelebrationReadinessMessage = '';

            return;
        }

        $village = Village::query()
            ->with('buildings')
            ->find((int) $this->editingVillageId);

        if (! $village instanceof Village) {
            $this->villageCelebrationReadinessMessage = '';

            return;
        }

        $this->updateVillageCelebrationReadinessMessage($village);
    }

    /**
     * Build the celebration warning for the currently selected settings.
     */
    protected function updateVillageCelebrationReadinessMessage(Village $village): void
    {
        $this->villageCelebrationReadinessMessage = '';

        if (! $this->villageCelebrationEnabledDraft) {
            return;
        }

        $townHallLevel = $village->buildings
            ->firstWhere('building_gid', 24)
            ?->current_level;

        if ($townHallLevel === null || (int) $townHallLevel < 1) {
            $this->villageCelebrationReadinessMessage = 'Cannot enable celebrations yet: this village does not have a Town Hall.';

            return;
        }

        if ($this->villageCelebrationTypeDraft === VillageCelebrationType::Great->value && (int) $townHallLevel < 10) {
            $this->villageCelebrationReadinessMessage = "Cannot use Great celebrations yet: Town Hall is level {$townHallLevel}, and level 10 is required.";
        }
    }

    /**
     * Resolve the localized tribe label used by the village modal.
     */
    protected function resolveTribeLabel(?int $tribeId): string
    {
        return match ($tribeId) {
            1 => 'Roman',
            2 => 'Teuton',
            3 => 'Gaul',
            default => 'Unknown tribe',
        };
    }

    /**
     * Reset the in-memory state used by the village build plan modal.
     */
    protected function resetVillageBuildPlanState(): void
    {
        $this->showVillageBuildPlanModal = false;
        $this->editingVillageId = null;
        $this->editingVillageName = '';
        $this->editingVillageIsCapital = false;
        $this->editingVillageTribeId = null;
        $this->editingVillageTribeLabel = '';
        $this->villageSettingsTab = 'generals';
        $this->villageFieldsAutomationDraft = true;
        $this->villageBuildingsAutomationDraft = true;
        $this->villageInheritProgramPriorityDraft = true;
        $this->villageFieldLevelCapModeDraft = VillageSetting::FieldCapInherit;
        $this->villageFieldLevelCapDraft = SystemSetting::defaultFieldLevelCap();
        $this->villageSendResourcesDraft = true;
        $this->villageSupplyResourcesDraft = true;
        $this->villageHeroResourcesDraft = true;
        $this->villageSupplyNegativeCropDraft = true;
        $this->villageSendMinResourcePercentageDraft = 30;
        $this->villageSendReserveResourcePercentageDraft = 10;
        $this->villageTradeMaxDurationMinutesDraft = $this->secondsToWholeMinutes(VillageSetting::defaultTradeMaxDurationSeconds());
        $this->villageCelebrationEnabledDraft = false;
        $this->villageCelebrationTypeDraft = VillageSetting::defaultCelebrationType()->value;
        $this->villageCelebrationMinimumCulturePointsDraft = VillageSetting::defaultCelebrationMinCulturePoints();
        $this->villageCelebrationReadinessMessage = '';
        $this->villageTroopTrainingEnabledDraft = false;
        $this->villagePrioritizeCropFieldsWhenNegativeDraft = true;
        $this->villageFieldPriorityDraft = [];
        $this->villageBuildingPlanDraft = [];
        $this->slotBuildingOptions = [];
    }

    /**
     * Reset the in-memory state used by the account settings modal.
     */
    protected function resetAccountSettingsState(): void
    {
        $this->showAccountSettingsModal = false;
        $this->editingAccountId = null;
        $this->editingAccountUsername = '';
        $this->accountSettingsTab = 'account';
        $this->accountInheritUserAgentDraft = true;
        $this->accountUserAgentDraft = '';
        $this->accountAcceptQuestsDraft = true;
        $this->accountProxyDrafts = [];
        $this->accountActiveProxyDraft = 'direct';
        $this->accountHeroUseGlobalSettingsDraft = true;
        $this->accountHeroAdventuresEnabledDraft = false;
        $this->accountHeroMinHealthDraft = 40;
        $this->accountHeroReviveEnabledDraft = false;
        $this->accountHeroAttributeUpgradeEnabledDraft = false;
        $this->accountHeroAttributeWeightsDraft = AccountSetting::defaultHeroAttributeWeights();
    }

    /**
     * Normalize hero attribute weights to the supported Travian attributes.
     *
     * @param  array<string, mixed>|null  $weights
     * @return array{power: int, offBonus: int, defBonus: int, productionPoints: int}
     */
    protected function normalizeHeroAttributeWeights(?array $weights): array
    {
        $defaults = AccountSetting::defaultHeroAttributeWeights();

        if (! is_array($weights)) {
            return $defaults;
        }

        return [
            'power' => max(0, (int) ($weights['power'] ?? $defaults['power'])),
            'offBonus' => max(0, (int) ($weights['offBonus'] ?? $defaults['offBonus'])),
            'defBonus' => max(0, (int) ($weights['defBonus'] ?? $defaults['defBonus'])),
            'productionPoints' => max(0, (int) ($weights['productionPoints'] ?? $defaults['productionPoints'])),
        ];
    }
}
