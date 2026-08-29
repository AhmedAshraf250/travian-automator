<?php

namespace App\Livewire\Dashboard\Concerns;

use App\Application\Travian\Data\BuildingEligibility;
use App\Application\Travian\TravianBuildingCatalog;
use App\Enums\VillageCelebrationType;
use App\Models\SystemSetting;
use App\Models\Village;
use App\Models\VillageBuilding;
use App\Models\VillageBuildingTarget;
use App\Models\VillageSetting;
use Illuminate\Validation\ValidationException;

trait ManagesVillageSettings
{
    use HasVillageTradeDrafts;

    /**
     * Controls the village build plan modal visibility.
     */
    public bool $showVillageBuildPlanModal = false;

    /**
     * Stores the currently edited village identifier for the plan modal.
     */
    public ?int $editingVillageId = null;

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
     * Stores the active village settings modal tab.
     */
    public string $villageSettingsTab = 'generals';

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
     * Stores whether the edited village may use hero resource items before marketplace support.
     */
    public bool $villageHeroResourcesDraft = true;

    /**
     * Stores whether celebration automation is enabled for the edited village.
     */
    public bool $villageCelebrationEnabledDraft = false;

    /**
     * Stores the preferred celebration type for the edited village.
     */
    public string $villageCelebrationTypeDraft = 'small';

    /**
     * Stores the minimum culture points required before starting a celebration.
     */
    public int $villageCelebrationMinimumCulturePointsDraft = 200;

    /** Stores whether celebration shortages may use hero resource items. */
    public bool $villageCelebrationUseHeroResourcesDraft = false;

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
        $this->ensureDefaultBuildingTargets($village, $tribeId);
        $village->load([
            'buildingTargets' => fn ($query) => $query->orderBy('priority')->orderBy('slot_id'),
        ]);

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
        $this->villageCelebrationUseHeroResourcesDraft = (bool) $settings->celebration_use_hero_resources;
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
            'villageCelebrationUseHeroResourcesDraft' => ['boolean'],
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
            'celebration_use_hero_resources' => $this->villageCelebrationUseHeroResourcesDraft,
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

            if ($buildingGid === 0) {
                $targetLevel = 0;
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

            if ($currentGid === 0 && $this->duplicateLimitedBuildingExistsBeforeMax($village, $buildingGid, $slotId)) {
                throw ValidationException::withMessages([
                    "villageBuildingPlanDraft.{$slotId}.building_gid" => 'Travian only allows another copy after the existing building reaches max level.',
                ]);
            }

            if ($currentGid === 0 && $this->buildingExistsOrIsPlannedElsewhere($village, $buildingGid, $slotId)) {
                throw ValidationException::withMessages([
                    "villageBuildingPlanDraft.{$slotId}.building_gid" => 'This building already exists or is planned in another slot.',
                ]);
            }

            if ($currentGid === 0 && ! $this->canKeepPlannedBeforeRequirements($buildingGid)) {
                $eligibility = TravianBuildingCatalog::canConstructInVillage($buildingGid, $village->account, $village);

                if (! $eligibility->allowed) {
                    throw ValidationException::withMessages([
                        "villageBuildingPlanDraft.{$slotId}.building_gid" => 'Travian requirements are not met for this building yet.',
                    ]);
                }
            }

            if ($currentGid !== 0 && $targetLevel < $currentLevel) {
                $village->buildingTargets()->where('slot_id', $slotId)->delete();

                continue;
            }

            $buildingName = TravianBuildingCatalog::nameForGid($buildingGid);

            if ($buildingName === null) {
                throw ValidationException::withMessages([
                    "villageBuildingPlanDraft.{$slotId}.building_gid" => 'This building is not recognized. Refresh the village and choose it again.',
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
        $this->dashboardRevision = $this->computeDashboardRevision();
        $village = $village->fresh([
            'runtimeState',
            'buildings' => fn ($query) => $query->orderBy('slot_id'),
            'buildingTargets' => fn ($query) => $query->orderBy('priority')->orderBy('slot_id'),
        ]);

        if ($village instanceof Village) {
            $this->slotBuildingOptions = $this->buildSlotBuildingOptions($village, $tribeId);
            $this->villageBuildingPlanDraft = $this->buildVillagePlanDraft($village, $tribeId);
            $this->updateVillageCelebrationReadinessMessage($village);
            $this->showVillageBuildPlanModal = true;
        }

        session()->flash('dashboard-banner', "{$village->name}: village settings were saved.");
    }

    protected function clampBuildingTargetLevel(int $buildingGid, int $targetLevel): int
    {
        $maxLevel = TravianBuildingCatalog::maxLevelForGid($buildingGid);

        if ($maxLevel !== null) {
            return max(0, min($targetLevel, (int) $maxLevel));
        }

        return max(0, $targetLevel);
    }

    protected function clampVillageFieldLevelCap(Village $village, int $fieldLevelCap): int
    {
        $maximum = $village->is_capital ? 20 : 10;

        return max(1, min($maximum, $fieldLevelCap));
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
            $targetLevel = (int) ($target?->target_level ?? 0);
            $priority = max(1, min(4, (int) ($target?->priority ?? 4)));

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

            if ($target === null && TravianBuildingCatalog::isDefaultManagedBuilding($currentGid)) {
                $targetLevel = TravianBuildingCatalog::defaultManagedTargetLevelForGid($currentGid) ?? 0;
                $priority = 1;
            }

            if ($target === null && $currentGid === 0 && $fixedSlotGid !== null && TravianBuildingCatalog::isDefaultManagedBuilding($fixedSlotGid)) {
                $targetLevel = TravianBuildingCatalog::defaultManagedTargetLevelForGid($fixedSlotGid) ?? 0;
                $priority = 1;
            }

            $draft[$slotId] = [
                'slot_id' => $slotId,
                'current_gid' => $currentGid,
                'current_name' => $this->resolveCurrentSlotLabel($currentSlot, $slotId, $tribeId),
                'current_level' => $currentLevel,
                'current_max_level' => TravianBuildingCatalog::finalLevelForGid($currentGid),
                'current_is_maxed' => $currentGid > 0
                    && TravianBuildingCatalog::finalLevelForGid($currentGid) !== null
                    && $currentLevel >= TravianBuildingCatalog::finalLevelForGid($currentGid),
                'building_gid' => (int) ($resolvedBuildingGid ?? 0),
                'target_level' => $targetLevel,
                'priority' => $priority,
                'is_enabled' => (bool) ($target?->is_enabled ?? true),
                'is_locked' => $currentGid !== 0 || $fixedSlotGid !== null,
            ];
        }

        return $draft;
    }

    protected function ensureDefaultBuildingTargets(Village $village, ?int $tribeId): void
    {
        $changed = false;

        foreach ([26, 39, 40] as $fixedSlotId) {
            $fixedGid = TravianBuildingCatalog::fixedSlotGidForSlot($fixedSlotId, $tribeId);

            if ($fixedGid === null) {
                continue;
            }

            if (! $village->buildings->firstWhere('slot_id', $fixedSlotId) instanceof VillageBuilding) {
                $village->buildings()->create([
                    'slot_id' => $fixedSlotId,
                    'building_gid' => 0,
                    'building_type' => null,
                    'current_level' => 0,
                ]);
                $village->load('buildings');
            }

            $fixedTargetLevel = TravianBuildingCatalog::defaultManagedTargetLevelForGid($fixedGid);

            if ($fixedTargetLevel === null || $village->buildingTargets->firstWhere('slot_id', $fixedSlotId) instanceof VillageBuildingTarget) {
                continue;
            }

            $fixedSlot = $village->buildings->firstWhere('slot_id', $fixedSlotId);

            if ($fixedSlot instanceof VillageBuilding && (int) $fixedSlot->building_gid === $fixedGid) {
                $finalLevel = TravianBuildingCatalog::finalLevelForGid($fixedGid);

                if ($finalLevel !== null && (int) $fixedSlot->current_level >= $finalLevel) {
                    continue;
                }
            }

            $village->buildingTargets()->create([
                'slot_id' => $fixedSlotId,
                'building_gid' => $fixedGid,
                'building_type' => TravianBuildingCatalog::nameForGid($fixedGid),
                'target_level' => $fixedTargetLevel,
                'priority' => 1,
                'is_enabled' => true,
            ]);

            $changed = true;
            $village->load('buildingTargets');
        }

        foreach ($village->buildings as $building) {
            if (! $building instanceof VillageBuilding) {
                continue;
            }

            $slotId = (int) $building->slot_id;
            $buildingGid = (int) $building->building_gid;

            if ($slotId < 19 || $slotId > 40 || ! TravianBuildingCatalog::isDefaultManagedBuilding($buildingGid)) {
                continue;
            }

            if ($buildingGid > 0
                && TravianBuildingCatalog::finalLevelForGid($buildingGid) !== null
                && (int) $building->current_level >= (int) TravianBuildingCatalog::finalLevelForGid($buildingGid)) {
                continue;
            }

            if ($village->buildingTargets->firstWhere('slot_id', $slotId) instanceof VillageBuildingTarget) {
                continue;
            }

            $village->buildingTargets()->create([
                'slot_id' => $slotId,
                'building_gid' => $buildingGid,
                'building_type' => TravianBuildingCatalog::nameForGid($buildingGid) ?? $building->building_type,
                'target_level' => TravianBuildingCatalog::defaultManagedTargetLevelForGid($buildingGid),
                'priority' => 1,
                'is_enabled' => true,
            ]);

            $changed = true;
        }

        foreach ([10, 11] as $requiredGid) {
            if ($this->hasBuildingOrTarget($village, $requiredGid)) {
                continue;
            }

            $slotId = $this->firstEmptyFlexibleSlot($village, $tribeId);

            if ($slotId === null) {
                continue;
            }

            if (! $village->buildings->firstWhere('slot_id', $slotId) instanceof VillageBuilding) {
                $village->buildings()->create([
                    'slot_id' => $slotId,
                    'building_gid' => 0,
                    'building_type' => null,
                    'current_level' => 0,
                ]);
                $village->load('buildings');
            }

            $village->buildingTargets()->create([
                'slot_id' => $slotId,
                'building_gid' => $requiredGid,
                'building_type' => TravianBuildingCatalog::nameForGid($requiredGid),
                'target_level' => TravianBuildingCatalog::defaultManagedTargetLevelForGid($requiredGid),
                'priority' => 1,
                'is_enabled' => true,
            ]);

            $changed = true;
            $village->load('buildingTargets');
        }

        if ($changed) {
            $village->load([
                'buildings' => fn ($query) => $query->orderBy('slot_id'),
                'buildingTargets' => fn ($query) => $query->orderBy('priority')->orderBy('slot_id'),
            ]);
        }
    }

    protected function hasBuildingOrTarget(Village $village, int $gid): bool
    {
        foreach ($village->buildings as $building) {
            if ($building instanceof VillageBuilding && (int) $building->building_gid === $gid) {
                return true;
            }
        }

        foreach ($village->buildingTargets as $target) {
            if ($target instanceof VillageBuildingTarget && (int) $target->building_gid === $gid && (int) $target->target_level > 0) {
                return true;
            }
        }

        return false;
    }

    protected function hasIncompleteBuildingOrTarget(Village $village, int $gid): bool
    {
        $finalLevel = TravianBuildingCatalog::finalLevelForGid($gid);

        foreach ($village->buildings as $building) {
            if (! $building instanceof VillageBuilding || (int) $building->building_gid !== $gid) {
                continue;
            }

            if ($finalLevel === null || (int) $building->current_level < $finalLevel) {
                return true;
            }
        }

        foreach ($village->buildingTargets as $target) {
            if (! $target instanceof VillageBuildingTarget || (int) $target->building_gid !== $gid || (int) $target->target_level < 1) {
                continue;
            }

            return true;
        }

        return false;
    }

    protected function firstEmptyFlexibleSlot(Village $village, ?int $tribeId): ?int
    {
        $currentSlots = $village->buildings->keyBy('slot_id');
        $targetSlots = $village->buildingTargets->keyBy('slot_id');

        foreach (range(19, 38) as $slotId) {
            $slot = $currentSlots->get($slotId);
            $target = $targetSlots->get($slotId);

            if (TravianBuildingCatalog::fixedSlotGidForSlot($slotId, $tribeId) !== null) {
                continue;
            }

            if ($slot instanceof VillageBuilding && (int) $slot->building_gid !== 0) {
                continue;
            }

            if ($target instanceof VillageBuildingTarget && (int) $target->target_level > 0) {
                continue;
            }

            return $slotId;
        }

        return null;
    }

    protected function duplicateLimitedBuildingExistsBeforeMax(Village $village, int $gid, int $slotId): bool
    {
        if (! TravianBuildingCatalog::allowsOnlyOneUntilMax($gid)) {
            return false;
        }

        $finalLevel = TravianBuildingCatalog::finalLevelForGid($gid);

        if ($finalLevel !== null) {
            foreach ($village->buildings as $building) {
                if (! $building instanceof VillageBuilding || (int) $building->slot_id === $slotId || (int) $building->building_gid !== $gid) {
                    continue;
                }

                if ((int) $building->current_level >= $finalLevel) {
                    return false;
                }
            }
        }

        foreach ($village->buildings as $building) {
            if (! $building instanceof VillageBuilding || (int) $building->slot_id === $slotId || (int) $building->building_gid !== $gid) {
                continue;
            }

            if ($finalLevel === null || (int) $building->current_level < $finalLevel) {
                return true;
            }
        }

        foreach ($village->buildingTargets as $target) {
            if (! $target instanceof VillageBuildingTarget || (int) $target->slot_id === $slotId || (int) $target->building_gid !== $gid) {
                continue;
            }

            if ((int) $target->target_level > 0) {
                return true;
            }
        }

        return false;
    }

    protected function buildingExistsOrIsPlannedElsewhere(Village $village, int $gid, int $slotId): bool
    {
        if ($gid < 1 || TravianBuildingCatalog::allowsMultipleCopiesAfterMax($gid)) {
            return false;
        }

        foreach ($village->buildings as $building) {
            if (! $building instanceof VillageBuilding || (int) $building->slot_id === $slotId) {
                continue;
            }

            if ((int) $building->building_gid === $gid) {
                return true;
            }
        }

        foreach ($village->buildingTargets as $target) {
            if (! $target instanceof VillageBuildingTarget || (int) $target->slot_id === $slotId) {
                continue;
            }

            if ((int) $target->building_gid === $gid && (int) $target->target_level > 0) {
                return true;
            }
        }

        return false;
    }

    protected function shouldHideBuildingOption(Village $village, int $gid, int $slotId): bool
    {
        if ($this->duplicateLimitedBuildingExistsBeforeMax($village, $gid, $slotId)) {
            return true;
        }

        if ($this->buildingExistsOrIsPlannedElsewhere($village, $gid, $slotId)) {
            return true;
        }

        return false;
    }

    protected function decorateBuildingOptionForVillage(Village $village, array $option): array
    {
        $gid = (int) $option['gid'];

        if ($this->canKeepPlannedBeforeRequirements($gid)) {
            return [
                ...$option,
                'selectable' => true,
                'unavailable_reason' => null,
            ];
        }

        $eligibility = TravianBuildingCatalog::canConstructInVillage($gid, $village->account, $village);

        return [
            ...$option,
            'selectable' => $eligibility->allowed,
            'unavailable_reason' => $eligibility->allowed ? null : $this->buildingOptionUnavailableReason($eligibility),
        ];
    }

    protected function buildingOptionUnavailableReason(BuildingEligibility $eligibility): string
    {
        if ($eligibility->blockedReason !== 'missing_requirements' || $eligibility->missingRequirements === []) {
            return match ($eligibility->blockedReason) {
                'capital_required' => 'Unavailable now: this building requires a capital village.',
                'account_unique_building_exists' => 'Unavailable now: this building already exists in another village.',
                'mutually_exclusive_building_exists' => 'Unavailable now: another exclusive building already exists.',
                'tribe_restricted' => 'Unavailable now: this building is not available for this tribe.',
                default => 'Unavailable now: Travian requirements are not met in this village.',
            };
        }

        $requirements = collect($eligibility->missingRequirements)
            ->map(function (array $requirement): string {
                $name = $requirement['name'] ?? ('gid '.$requirement['gid']);
                $requiredLevel = (int) $requirement['required_level'];

                if ($requiredLevel <= 0) {
                    return "{$name} required";
                }

                return "{$name} Lv {$requiredLevel} required";
            })
            ->implode(', ');

        return "Unavailable now: {$requirements}.";
    }

    protected function canKeepPlannedBeforeRequirements(int $gid): bool
    {
        return in_array($gid, [10, 11, 15, 16, 31, 32, 33], true);
    }

    /**
     * Build the per-slot selectable building options for the current village.
     *
     * @return array<int, list<array{gid: int, label: string, category: int|null, icon: string|null, selectable: bool, unavailable_reason: string|null}>>
     */
    protected function buildSlotBuildingOptions(Village $village, ?int $tribeId): array
    {
        $currentSlots = $village->buildings->keyBy('slot_id');
        $genericOptions = collect(TravianBuildingCatalog::buildingOptionsForTribe($tribeId))
            ->map(fn (array $option): array => [
                ...$option,
                'icon' => $this->buildingIconPathForGid((int) $option['gid']),
            ])
            ->keyBy('gid');
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

            $slotOptions[$slotId] = $genericOptions
                ->reject(fn (array $option): bool => $this->shouldHideBuildingOption($village, (int) $option['gid'], $slotId))
                ->map(fn (array $option): array => $this->decorateBuildingOptionForVillage($village, $option))
                ->values()
                ->all();
        }

        return $slotOptions;
    }

    /**
     * Resolve one select option payload for a gid.
     *
     * @return array{gid: int, label: string, category: int|null, icon: string|null, selectable: bool, unavailable_reason: string|null}|null
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
            'icon' => $this->buildingIconPathForGid($gid),
            'selectable' => true,
            'unavailable_reason' => null,
        ];
    }

    protected function buildingIconPathForGid(int $gid): ?string
    {
        if ($gid < 1) {
            return null;
        }

        foreach ([
            "assets/buildings-icons/type{$gid}_small.png",
            "assets/buildings-icons/type{$gid}_teahouse_small.png",
        ] as $candidate) {
            if (file_exists(public_path($candidate))) {
                return $candidate;
            }
        }

        return null;
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
        $this->villageCelebrationUseHeroResourcesDraft = false;
        $this->villageCelebrationReadinessMessage = '';
        $this->villagePrioritizeCropFieldsWhenNegativeDraft = true;
        $this->villageFieldPriorityDraft = [];
        $this->villageBuildingPlanDraft = [];
        $this->slotBuildingOptions = [];
    }
}
