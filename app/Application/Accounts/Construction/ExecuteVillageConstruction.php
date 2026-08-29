<?php

namespace App\Application\Accounts\Construction;

use App\Application\Accounts\Connection\RecordsAccountConnectionFailure;
use App\Application\Accounts\Construction\Data\BuildPageAnalysis;
use App\Application\Accounts\Hero\UseHeroResourcesForConstruction;
use App\Application\Accounts\Session\Actions\TravianLoginAction;
use App\Application\Accounts\Session\Contracts\AccountSession;
use App\Application\Accounts\Session\Data\SessionResponse;
use App\Application\Accounts\Sync\Data\ParsedConstructionQueueEntry;
use App\Application\Accounts\Sync\Data\ParsedDorf1Overview;
use App\Application\Accounts\Sync\Data\ParsedDorf2Overview;
use App\Application\Accounts\Sync\Data\ParsedVillageSlot;
use App\Application\Accounts\Sync\Parsers\Dorf1OverviewParser;
use App\Application\Accounts\Sync\Parsers\Dorf2OverviewParser;
use App\Application\Accounts\Sync\PersistVillageOverview;
use App\Application\Accounts\Trading\ExecuteVillageResourceTransfer;
use App\Application\Travian\Data\BuildingEligibility;
use App\Application\Travian\TravianBuildingCatalog;
use App\Enums\ActivityLogStatus;
use App\Enums\ActivityType;
use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\SystemSetting;
use App\Models\Village;
use App\Models\VillageBuilding;
use App\Models\VillageBuildingTarget;
use App\Models\VillageSetting;
use Throwable;

/**
 * Executes one automation construction pass for a single village.
 */
class ExecuteVillageConstruction
{
    /**
     * Create a new construction executor instance.
     */
    public function __construct(
        protected TravianLoginAction $travianLoginAction,
        protected BuildPageAnalyzer $buildPageAnalyzer,
        protected UseHeroResourcesForConstruction $useHeroResourcesForConstruction,
        protected ExecuteVillageResourceTransfer $executeVillageResourceTransfer,
        protected Dorf1OverviewParser $dorf1OverviewParser,
        protected Dorf2OverviewParser $dorf2OverviewParser,
        protected PersistVillageOverview $persistVillageOverview,
        protected RecordsAccountConnectionFailure $recordsAccountConnectionFailure,
    ) {}

    /**
     * Execute the next valid construction action for one village.
     */
    public function handle(Account $account, Village $village, AccountSession $session): void
    {
        try {
            if (! $village->is_active) {
                return;
            }

            $switchResponse = $session->get(
                $this->resolveVillageSwitchUri($village),
                $this->documentRequestOptions($this->absoluteUri((string) config('travian.paths.overview', '/dorf1.php'), $account)),
            );

            $settings = $village->settings;
            $runtimeState = $village->runtimeState;

            if (! $settings instanceof VillageSetting || $runtimeState === null) {
                return;
            }

            if ($settings->pause_fields && $settings->pause_buildings) {
                return;
            }

            $tribeId = $runtimeState->tribe_id !== null ? (int) $runtimeState->tribe_id : null;

            if ($tribeId === null) {
                return;
            }

            $queueAvailability = $this->resolveQueueAvailability(
                is_array($runtimeState->construction_entries) ? $runtimeState->construction_entries : [],
                $tribeId,
            );

            $fieldCandidates = ! $settings->pause_fields && $queueAvailability['field']
                ? $this->selectFieldCandidates($village, $settings)
                : [];
            $buildingCandidates = ! $settings->pause_buildings && $queueAvailability['building']
                ? $this->selectBuildingCandidates($account, $village)
                : [];
            $fieldCandidates = $this->applySchedulePreferences($fieldCandidates, $settings, 'field');
            $buildingCandidates = $this->applySchedulePreferences($buildingCandidates, $settings, 'building');
            $visibleScheduleKeys = $this->visibleScheduleKeys($fieldCandidates, $buildingCandidates, $settings);
            $fieldCandidates = $this->filterCandidatesByScheduleKeys($fieldCandidates, $visibleScheduleKeys, 'field');
            $buildingCandidates = $this->filterCandidatesByScheduleKeys($buildingCandidates, $visibleScheduleKeys, 'building');
            $firstResourceShortage = null;

            if (TravianBuildingCatalog::isRomanTribe($tribeId)) {
                foreach ($this->resolveRomanQueueOrder($fieldCandidates, $buildingCandidates, $settings) as $queueKind) {
                    if ($queueKind === 'field' && $fieldCandidates !== [] && $queueAvailability['field']) {
                        $fieldResult = $this->executeFirstFieldCandidate($account, $village, $session, $fieldCandidates, $settings);
                        $fieldExecuted = $fieldResult['executed'];

                        if (! $fieldExecuted && $fieldResult['resource_shortage'] !== null) {
                            $fieldExecuted = $this->retryConstructionAfterHeroResources(
                                $account,
                                $village,
                                $session,
                                $fieldResult['resource_shortage'],
                            );

                            if (! $fieldExecuted) {
                                $firstResourceShortage ??= $fieldResult['resource_shortage'];
                            }
                        }

                        if ($fieldResult['blocked_by_schedule_stop']) {
                            break;
                        }

                        continue;
                    }

                    if ($queueKind === 'building' && $buildingCandidates !== [] && $queueAvailability['building']) {
                        $buildingResult = $this->executeFirstBuildingCandidate($account, $village, $session, $buildingCandidates, $switchResponse->effectiveUri, $settings);
                        $buildingExecuted = $buildingResult['executed'];

                        if (! $buildingExecuted && $buildingResult['resource_shortage'] !== null) {
                            $buildingExecuted = $this->retryConstructionAfterHeroResources(
                                $account,
                                $village,
                                $session,
                                $buildingResult['resource_shortage'],
                            );

                            if (! $buildingExecuted) {
                                $firstResourceShortage ??= $buildingResult['resource_shortage'];
                            }
                        }

                        if ($buildingResult['blocked_by_schedule_stop']) {
                            break;
                        }
                    }
                }

                if ($firstResourceShortage !== null) {
                    $this->executeVillageResourceTransfer->handle(
                        $account,
                        $village,
                        $session,
                        $firstResourceShortage['payload'],
                        $firstResourceShortage['analysis'],
                    );
                }

                return;
            }

            foreach ($this->resolveSingleQueueOrder($fieldCandidates, $buildingCandidates, $settings) as $queueKind) {
                if ($queueKind === 'field' && $fieldCandidates !== [] && $queueAvailability['field']) {
                    $fieldResult = $this->executeFirstFieldCandidate($account, $village, $session, $fieldCandidates, $settings);

                    if ($fieldResult['executed']) {
                        return;
                    }

                    $firstResourceShortage ??= $fieldResult['resource_shortage'];

                    if ($fieldResult['blocked_by_schedule_stop']) {
                        break;
                    }

                    continue;
                }

                if ($queueKind === 'building' && $buildingCandidates !== [] && $queueAvailability['building']) {
                    $buildingResult = $this->executeFirstBuildingCandidate($account, $village, $session, $buildingCandidates, $switchResponse->effectiveUri, $settings);

                    if ($buildingResult['executed']) {
                        return;
                    }

                    $firstResourceShortage ??= $buildingResult['resource_shortage'];

                    if ($buildingResult['blocked_by_schedule_stop']) {
                        break;
                    }
                }
            }

            if ($firstResourceShortage !== null) {
                if ($this->retryConstructionAfterHeroResources($account, $village, $session, $firstResourceShortage)) {
                    return;
                }

                $this->executeVillageResourceTransfer->handle(
                    $account,
                    $village,
                    $session,
                    $firstResourceShortage['payload'],
                    $firstResourceShortage['analysis'],
                );
            }
        } catch (Throwable $throwable) {
            if ($this->recordsAccountConnectionFailure->shouldBackOff($throwable)) {
                throw $throwable;
            }

            ActivityLog::query()->create([
                'account_id' => $account->id,
                'village_id' => $village->id,
                'activity_type' => ActivityType::Build,
                'status' => ActivityLogStatus::Failed,
                'message' => 'Village construction automation failed: '.$throwable->getMessage(),
                'executed_at' => now(),
            ]);
        }
    }

    /**
     * Determine which construction queues are still available.
     *
     * @param  list<array<string, mixed>>  $constructionEntries
     * @return array{field: bool, building: bool}
     */
    protected function resolveQueueAvailability(array $constructionEntries, ?int $tribeId): array
    {
        if (! TravianBuildingCatalog::isRomanTribe($tribeId)) {
            $queueIsOpen = $constructionEntries === [];

            return [
                'field' => $queueIsOpen,
                'building' => $queueIsOpen,
            ];
        }

        $availability = [
            'field' => true,
            'building' => true,
        ];

        foreach ($constructionEntries as $constructionEntry) {
            $queueKind = TravianBuildingCatalog::queueKindForName($constructionEntry['building_name'] ?? null);

            if ($queueKind === 'field') {
                $availability['field'] = false;

                continue;
            }

            if ($queueKind === 'building') {
                $availability['building'] = false;

                continue;
            }

            return [
                'field' => false,
                'building' => false,
            ];
        }

        return $availability;
    }

    /**
     * Select ordered field candidates according to village priorities.
     *
     * @return list<array{slot: VillageBuilding, field_key: string}>
     */
    protected function selectFieldCandidates(Village $village, VillageSetting $settings): array
    {
        $priorityMap = $this->resolveEffectiveFieldPriority($settings);
        $prioritizeCropRecovery = $this->shouldPrioritizeCropFieldsForNegativeProduction($village, $settings);
        $fieldLevelCap = $this->effectiveFieldLevelCap($village, $settings);
        $fieldSlots = $village->buildings
            ->filter(static fn (VillageBuilding $slot): bool => $slot->slot_id >= 1
                && $slot->slot_id <= 18
                && $slot->building_gid >= 1
                && $slot->building_gid <= 4
                && (bool) $slot->automation_enabled)
            ->values()
            ->all();

        $candidates = [];

        foreach ($fieldSlots as $fieldSlot) {
            if (! $fieldSlot instanceof VillageBuilding) {
                continue;
            }

            $fieldKey = TravianBuildingCatalog::fieldKeyForGid((int) $fieldSlot->building_gid);

            if ($fieldKey === null) {
                continue;
            }

            if ((int) $fieldSlot->current_level >= $fieldLevelCap) {
                continue;
            }

            if (! $this->passesFieldPriorityLeadGuard($fieldSlot, $fieldKey, $fieldSlots, $priorityMap)) {
                continue;
            }

            $candidate = [
                'slot' => $fieldSlot,
                'field_key' => $fieldKey,
            ];

            if ($this->candidateRecentlyBlockedByBuildPage($village, $candidate, 'field')) {
                continue;
            }

            $candidates[] = $candidate;
        }

        usort($candidates, function (array $left, array $right) use ($priorityMap): int {
            $leftPriority = $priorityMap[$left['field_key']] ?? 999;
            $rightPriority = $priorityMap[$right['field_key']] ?? 999;

            if ($leftPriority !== $rightPriority) {
                return $leftPriority <=> $rightPriority;
            }

            if ((int) $left['slot']->current_level !== (int) $right['slot']->current_level) {
                return (int) $left['slot']->current_level <=> (int) $right['slot']->current_level;
            }

            return (int) $left['slot']->slot_id <=> (int) $right['slot']->slot_id;
        });

        if ($prioritizeCropRecovery) {
            $candidates = $this->prependCropRecoveryCandidates($village, $settings, $candidates);
        }

        return array_values($candidates);
    }

    /**
     * Move crop fields to the front when the village is burning crop.
     *
     * @param  list<array{slot: VillageBuilding, field_key: string}>  $candidates
     * @return list<array{slot: VillageBuilding, field_key: string}>
     */
    protected function prependCropRecoveryCandidates(Village $village, VillageSetting $settings, array $candidates): array
    {
        $cropCandidates = $this->selectCropRecoveryCandidates($village, $settings);

        if ($cropCandidates === []) {
            return $candidates;
        }

        $cropSlotIds = array_map(
            static fn (array $candidate): int => (int) $candidate['slot']->slot_id,
            $cropCandidates,
        );

        $remainingCandidates = array_values(array_filter(
            $candidates,
            static fn (array $candidate): bool => ! in_array((int) $candidate['slot']->slot_id, $cropSlotIds, true),
        ));

        return [
            ...$cropCandidates,
            ...$remainingCandidates,
        ];
    }

    /**
     * Select crop fields as an emergency fallback when Travian asks for food first.
     *
     * @return list<array{slot: VillageBuilding, field_key: string}>
     */
    protected function selectCropRecoveryCandidates(Village $village, ?VillageSetting $settings = null): array
    {
        $fieldLevelCap = $this->effectiveFieldLevelCap($village, $settings ?? $village->settings ?? new VillageSetting);

        $candidates = $village->buildings
            ->filter(static fn (VillageBuilding $slot): bool => $slot->slot_id >= 1
                && $slot->slot_id <= 18
                && (int) $slot->building_gid === 4
                && (int) $slot->current_level < $fieldLevelCap
                && (bool) $slot->automation_enabled)
            ->sortBy([
                ['current_level', 'asc'],
                ['slot_id', 'asc'],
            ])
            ->map(static fn (VillageBuilding $slot): array => [
                'slot' => $slot,
                'field_key' => 'crop',
            ])
            ->values()
            ->all();

        return $candidates;
    }

    protected function effectiveFieldLevelCap(Village $village, VillageSetting $settings): int
    {
        $mode = in_array((string) $settings->field_level_cap_mode, VillageSetting::fieldLevelCapModes(), true)
            ? (string) $settings->field_level_cap_mode
            : VillageSetting::FieldCapInherit;

        $cap = match ($mode) {
            VillageSetting::FieldCapCustom => (int) ($settings->field_level_cap ?? SystemSetting::defaultFieldLevelCap()),
            VillageSetting::FieldCapDisabled => 20,
            default => (int) (SystemSetting::constructionDefaults()['field_level_cap'] ?? SystemSetting::defaultFieldLevelCap()),
        };

        if (! $village->is_capital) {
            $cap = min($cap, 10);
        }

        return max(1, min(20, $cap));
    }

    /**
     * Prevent any resource family from running beyond the allowed priority gap.
     *
     * @param  list<VillageBuilding>  $fieldSlots
     * @param  array{wood: int, clay: int, iron: int, crop: int}  $priorityMap
     */
    protected function passesFieldPriorityLeadGuard(
        VillageBuilding $candidateSlot,
        string $candidateFieldKey,
        array $fieldSlots,
        array $priorityMap,
    ): bool {
        $candidatePriority = $priorityMap[$candidateFieldKey] ?? 999;
        $candidateNextLevel = (int) $candidateSlot->current_level + 1;
        $minLevelByField = [];

        foreach ($fieldSlots as $fieldSlot) {
            if (! $fieldSlot instanceof VillageBuilding) {
                continue;
            }

            $fieldKey = TravianBuildingCatalog::fieldKeyForGid((int) $fieldSlot->building_gid);

            if ($fieldKey === null) {
                continue;
            }

            $fieldLevel = (int) $fieldSlot->current_level;
            $minLevelByField[$fieldKey] = isset($minLevelByField[$fieldKey])
                ? min($minLevelByField[$fieldKey], $fieldLevel)
                : $fieldLevel;
        }

        foreach ($minLevelByField as $fieldKey => $minLevel) {
            if ($fieldKey === $candidateFieldKey) {
                continue;
            }

            $otherPriority = $priorityMap[$fieldKey] ?? 999;

            $allowedLead = max(1, abs($otherPriority - $candidatePriority));

            if ($candidateNextLevel > ((int) $minLevel + $allowedLead)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Try field candidates in order until one can actually issue a build action.
     *
     * @param  list<array{slot: VillageBuilding, field_key: string}>  $candidates
     * @return array{executed: bool, blocked_by_schedule_stop: bool, resource_shortage: array{payload: array<string, mixed>, analysis: BuildPageAnalysis}|null}
     */
    protected function executeFirstFieldCandidate(
        Account $account,
        Village $village,
        AccountSession $session,
        array $candidates,
        VillageSetting $settings,
    ): array {
        $cropFallbackNeeded = false;
        $firstResourceShortage = null;

        foreach ($candidates as $candidate) {
            $result = $this->executeFieldCandidate($account, $village, $session, $candidate);
            $firstResourceShortage ??= $result['resource_shortage'];

            if ($result['executed']) {
                return [
                    'executed' => true,
                    'blocked_by_schedule_stop' => false,
                    'resource_shortage' => null,
                ];
            }

            if ($this->candidateIsHeld($candidate, $settings, 'field')) {
                return [
                    'executed' => false,
                    'blocked_by_schedule_stop' => true,
                    'resource_shortage' => $firstResourceShortage,
                ];
            }

            if ($result['blocked_reason'] === 'crop_field_required') {
                $cropFallbackNeeded = true;
            }
        }

        if (! $cropFallbackNeeded) {
            return [
                'executed' => false,
                'blocked_by_schedule_stop' => false,
                'resource_shortage' => $firstResourceShortage,
            ];
        }

        foreach ($this->selectCropRecoveryCandidates($village, $settings) as $candidate) {
            $result = $this->executeFieldCandidate($account, $village, $session, $candidate);
            $firstResourceShortage ??= $result['resource_shortage'];

            if ($result['executed']) {
                return [
                    'executed' => true,
                    'blocked_by_schedule_stop' => false,
                    'resource_shortage' => null,
                ];
            }
        }

        return [
            'executed' => false,
            'blocked_by_schedule_stop' => false,
            'resource_shortage' => $firstResourceShortage,
        ];
    }

    /**
     * Try building candidates in order until one can issue a build action.
     *
     * @param  list<array{
     *     target: VillageBuildingTarget,
     *     current_slot: VillageBuilding,
     *     target_gid: int,
     *     mode: 'upgrade'|'construct'
     * }>  $candidates
     * @return array{executed: bool, blocked_by_schedule_stop: bool, resource_shortage: array{payload: array<string, mixed>, analysis: BuildPageAnalysis}|null}
     */
    protected function executeFirstBuildingCandidate(
        Account $account,
        Village $village,
        AccountSession $session,
        array $candidates,
        string $villageReferer,
        VillageSetting $settings,
    ): array {
        $villageCenterResponse = $session->get(
            (string) config('travian.paths.village_center', '/dorf2.php'),
            $this->documentRequestOptions($villageReferer),
        );
        $liveDorf2Overview = $this->parseLiveDorf2Overview($villageCenterResponse);
        $firstResourceShortage = null;

        foreach ($candidates as $candidate) {
            $originalCandidate = $candidate;

            if ($liveDorf2Overview instanceof ParsedDorf2Overview) {
                $candidate = $this->confirmBuildingCandidateAgainstLiveDorf2($village, $candidate, $liveDorf2Overview);

                if ($candidate === null) {
                    if ($this->candidateIsHeld($originalCandidate, $settings, 'building')) {
                        return [
                            'executed' => false,
                            'blocked_by_schedule_stop' => true,
                            'resource_shortage' => null,
                        ];
                    }

                    continue;
                }
            }

            $result = $this->executeBuildingCandidate($account, $village, $session, $candidate, $villageCenterResponse->effectiveUri);
            $firstResourceShortage ??= $result['resource_shortage'];

            if ($result['executed']) {
                return [
                    'executed' => true,
                    'blocked_by_schedule_stop' => false,
                    'resource_shortage' => null,
                ];
            }

            if ($this->candidateIsHeld($candidate, $settings, 'building')) {
                return [
                    'executed' => false,
                    'blocked_by_schedule_stop' => true,
                    'resource_shortage' => $firstResourceShortage,
                ];
            }
        }

        return [
            'executed' => false,
            'blocked_by_schedule_stop' => false,
            'resource_shortage' => $firstResourceShortage,
        ];
    }

    /**
     * Parse the live village center page when available.
     */
    protected function parseLiveDorf2Overview(SessionResponse $villageCenterResponse): ?ParsedDorf2Overview
    {
        try {
            return $this->dorf2OverviewParser->parse($villageCenterResponse->body);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Re-check a building candidate against the live dorf2 snapshot before pressing build.
     *
     * @param  array{target: VillageBuildingTarget, current_slot: VillageBuilding, target_gid: int, mode: 'upgrade'|'construct'}  $candidate
     * @return array{target: VillageBuildingTarget, current_slot: VillageBuilding, target_gid: int, mode: 'upgrade'|'construct'}|null
     */
    protected function confirmBuildingCandidateAgainstLiveDorf2(Village $village, array $candidate, ParsedDorf2Overview $dorf2Overview): ?array
    {
        $target = $candidate['target'];
        $currentSlot = $candidate['current_slot'];
        $targetGid = (int) $candidate['target_gid'];
        $liveSlot = collect($dorf2Overview->buildingSlots)
            ->first(fn (ParsedVillageSlot $slot): bool => $slot->slotId === (int) $currentSlot->slot_id);

        if (! $liveSlot instanceof ParsedVillageSlot) {
            return $candidate;
        }

        $liveGid = (int) $liveSlot->buildingGid;
        $liveLevel = (int) $liveSlot->currentLevel;

        $currentSlot->forceFill([
            'building_gid' => $liveGid,
            'building_type' => $liveSlot->buildingName,
            'current_level' => $liveLevel,
            'is_under_construction' => false,
            'finish_at' => null,
        ])->save();

        if ($liveGid !== 0 && $liveGid !== $targetGid) {
            return null;
        }

        if ($liveLevel >= (int) $target->target_level) {
            $target->delete();

            return null;
        }

        if ($liveGid === 0 && $candidate['mode'] === 'upgrade') {
            return null;
        }

        $candidate['current_slot'] = $currentSlot->refresh();
        $candidate['mode'] = $liveGid === 0 || $liveLevel < 1 ? 'construct' : 'upgrade';

        return $candidate;
    }

    /**
     * Select building targets that can be upgraded or constructed in priority order.
     *
     * @return list<array{
     *     target: VillageBuildingTarget,
     *     current_slot: VillageBuilding,
     *     target_gid: int,
     *     mode: 'upgrade'|'construct'
     * }>
     */
    protected function selectBuildingCandidates(Account $account, Village $village): array
    {
        $candidates = [];
        $candidateKeys = [];
        $this->ensureDefaultBuildingTargets($village);

        $village->loadMissing(['buildingTargets', 'buildings']);
        $targets = $village->buildingTargets->sortBy('priority')->values();

        foreach ($targets as $target) {
            if (! $target instanceof VillageBuildingTarget || ! $target->is_enabled) {
                continue;
            }

            $targetLevel = (int) $target->target_level;

            if ($targetLevel < 1) {
                continue;
            }

            $targetGid = (int) $target->building_gid;

            if ($targetGid === 0) {
                $targetGid = TravianBuildingCatalog::gidForName($target->building_type) ?? 0;

                if ($targetGid > 0) {
                    $target->forceFill([
                        'building_gid' => $targetGid,
                    ])->save();
                }
            }

            if ($targetGid === 0) {
                continue;
            }

            $targetLevel = $this->clampTargetLevel($target, $targetGid, $targetLevel);

            $targetSlotId = TravianBuildingCatalog::fixedSlotForGid(
                $targetGid,
                $village->runtimeState?->tribe_id !== null ? (int) $village->runtimeState->tribe_id : null,
            ) ?? (int) $target->slot_id;
            $currentSlot = $village->buildings->firstWhere('slot_id', $targetSlotId);

            if (! $currentSlot instanceof VillageBuilding) {
                continue;
            }

            if (! (bool) $currentSlot->automation_enabled) {
                continue;
            }

            $currentGid = (int) $currentSlot->building_gid;
            $currentLevel = (int) $currentSlot->current_level;

            if ($currentGid !== 0 && $currentGid !== $targetGid) {
                continue;
            }

            $finalLevel = TravianBuildingCatalog::finalLevelForGid($targetGid);

            if ($finalLevel !== null && $currentLevel >= $finalLevel) {
                $target->delete();

                continue;
            }

            if ($currentGid === 0) {
                $eligibility = TravianBuildingCatalog::canConstructInVillage($targetGid, $account, $village);

                if (! $eligibility->allowed) {
                    $prerequisiteCandidate = $this->selectMissingRequirementCandidate($account, $village, $targets, $eligibility->missingRequirements);

                    if ($prerequisiteCandidate !== null) {
                        $this->appendBuildingCandidate($candidates, $candidateKeys, $prerequisiteCandidate);

                        continue;
                    }

                    $this->recordBlockedBuildingCandidate($account, $village, $target, $eligibility);

                    continue;
                }

                $this->appendBuildingCandidate($candidates, $candidateKeys, [
                    'target' => $target,
                    'current_slot' => $currentSlot,
                    'target_gid' => $targetGid,
                    'mode' => 'construct',
                ]);

                continue;
            }

            if ($currentLevel >= $targetLevel) {
                continue;
            }

            $this->appendBuildingCandidate($candidates, $candidateKeys, [
                'target' => $target,
                'current_slot' => $currentSlot,
                'target_gid' => $targetGid,
                'mode' => 'upgrade',
            ]);
        }

        return $this->orderBuildingCandidatesForPriorityBalance($candidates);
    }

    /**
     * Keep equal-priority building targets balanced by upgrading the lower current level first.
     *
     * @param  list<array{target: VillageBuildingTarget, current_slot: VillageBuilding, target_gid: int, mode: 'upgrade'|'construct'}>  $candidates
     * @return list<array{target: VillageBuildingTarget, current_slot: VillageBuilding, target_gid: int, mode: 'upgrade'|'construct'}>
     */
    protected function orderBuildingCandidatesForPriorityBalance(array $candidates): array
    {
        $indexedCandidates = array_map(
            static fn (array $candidate, int $index): array => [
                'candidate' => $candidate,
                'index' => $index,
                'priority' => (int) $candidate['target']->priority,
                'current_level' => (int) $candidate['current_slot']->current_level,
                'slot_id' => (int) $candidate['current_slot']->slot_id,
            ],
            $candidates,
            array_keys($candidates),
        );

        usort($indexedCandidates, static function (array $left, array $right): int {
            foreach (['priority', 'current_level', 'slot_id', 'index'] as $key) {
                if ($left[$key] !== $right[$key]) {
                    return $left[$key] <=> $right[$key];
                }
            }

            return 0;
        });

        return array_values(array_map(
            static fn (array $row): array => $row['candidate'],
            $indexedCandidates,
        ));
    }

    protected function ensureDefaultBuildingTargets(Village $village): void
    {
        $this->ensureEssentialMainBuildingTarget($village);
        $this->ensureObservedManagedBuildingTargets($village);
        $this->ensureMissingWarehouseTargets($village);
    }

    protected function ensureEssentialMainBuildingTarget(Village $village): void
    {
        $mainSlot = $village->buildings->firstWhere('slot_id', 26);

        if (! $mainSlot instanceof VillageBuilding) {
            $mainSlot = $village->buildings()->create([
                'slot_id' => 26,
                'building_gid' => 0,
                'building_type' => null,
                'current_level' => 0,
            ]);

            $village->setRelation('buildings', $village->buildings->push($mainSlot));
        }

        if ((int) $mainSlot->building_gid !== 0 || (int) $mainSlot->current_level > 0) {
            return;
        }

        $targetLevel = TravianBuildingCatalog::defaultManagedTargetLevelForGid(15) ?? 14;
        $existingTarget = $village->buildingTargets->firstWhere('slot_id', 26);

        if ($existingTarget instanceof VillageBuildingTarget && (int) $existingTarget->target_level >= $targetLevel) {
            return;
        }

        $village->buildingTargets()->updateOrCreate(
            ['slot_id' => 26],
            [
                'building_gid' => 15,
                'building_type' => TravianBuildingCatalog::nameForGid(15),
                'target_level' => $targetLevel,
                'priority' => 1,
                'is_enabled' => true,
            ],
        );

        $village->unsetRelation('buildingTargets');
        $village->load('buildingTargets');
    }

    protected function ensureObservedManagedBuildingTargets(Village $village): void
    {
        $changed = false;

        foreach ($village->buildings as $building) {
            if (! $building instanceof VillageBuilding) {
                continue;
            }

            $slotId = (int) $building->slot_id;
            $buildingGid = (int) $building->building_gid;

            if ($slotId < 19 || $slotId > 40 || ! TravianBuildingCatalog::isDefaultManagedBuilding($buildingGid)) {
                continue;
            }

            $finalLevel = TravianBuildingCatalog::finalLevelForGid($buildingGid);

            if ($finalLevel !== null && (int) $building->current_level >= $finalLevel) {
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

        if ($changed) {
            $village->unsetRelation('buildingTargets');
            $village->load('buildingTargets');
        }
    }

    protected function ensureMissingWarehouseTargets(Village $village): void
    {
        $changed = false;

        foreach ([10, 11] as $requiredGid) {
            if ($this->hasBuildingOrTarget($village, $requiredGid)) {
                continue;
            }

            $slotId = $this->firstEmptyFlexibleSlot($village);

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
            $village->unsetRelation('buildingTargets');
            $village->load('buildingTargets');
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

    protected function firstEmptyFlexibleSlot(Village $village): ?int
    {
        $currentSlots = $village->buildings->keyBy('slot_id');
        $targetSlots = $village->buildingTargets->keyBy('slot_id');

        foreach (range(19, 38) as $slotId) {
            $slot = $currentSlots->get($slotId);
            $target = $targetSlots->get($slotId);

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

    /**
     * Prefer a configured prerequisite target before logging the desired building as blocked.
     *
     * @param  iterable<int, VillageBuildingTarget>  $targets
     * @param  list<array{gid:int, name:string|null, required_level:int, current_level:int}>  $missingRequirements
     * @return array{target: VillageBuildingTarget, current_slot: VillageBuilding, target_gid: int, mode: 'upgrade'|'construct'}|null
     */
    protected function selectMissingRequirementCandidate(Account $account, Village $village, iterable $targets, array $missingRequirements, int $depth = 0): ?array
    {
        if ($depth > 2) {
            return null;
        }

        foreach ($missingRequirements as $missingRequirement) {
            $requiredGid = (int) ($missingRequirement['gid'] ?? 0);
            $requiredLevel = (int) ($missingRequirement['required_level'] ?? 0);

            if ($requiredGid < 1 || $requiredLevel < 1) {
                continue;
            }

            foreach ($targets as $target) {
                if (! $target instanceof VillageBuildingTarget || ! $target->is_enabled) {
                    continue;
                }

                $targetGid = $this->resolveTargetGid($target);

                if ($targetGid !== $requiredGid || (int) $target->target_level < $requiredLevel) {
                    continue;
                }

                $targetSlotId = TravianBuildingCatalog::fixedSlotForGid(
                    $targetGid,
                    $village->runtimeState?->tribe_id !== null ? (int) $village->runtimeState->tribe_id : null,
                ) ?? (int) $target->slot_id;
                $currentSlot = $village->buildings->firstWhere('slot_id', $targetSlotId);

                if (! $currentSlot instanceof VillageBuilding) {
                    continue;
                }

                if (! (bool) $currentSlot->automation_enabled) {
                    continue;
                }

                $currentGid = (int) $currentSlot->building_gid;
                $currentLevel = (int) $currentSlot->current_level;

                if ($currentGid !== 0 && $currentGid !== $targetGid) {
                    continue;
                }

                if ($currentLevel >= $requiredLevel) {
                    continue;
                }

                if ($currentGid === 0) {
                    $eligibility = TravianBuildingCatalog::canConstructInVillage($targetGid, $account, $village);

                    if (! $eligibility->allowed) {
                        return $this->selectMissingRequirementCandidate($account, $village, $targets, $eligibility->missingRequirements, $depth + 1);
                    }

                    return [
                        'target' => $target,
                        'current_slot' => $currentSlot,
                        'target_gid' => $targetGid,
                        'mode' => 'construct',
                    ];
                }

                return [
                    'target' => $target,
                    'current_slot' => $currentSlot,
                    'target_gid' => $targetGid,
                    'mode' => 'upgrade',
                ];
            }
        }

        return null;
    }

    protected function clampTargetLevel(VillageBuildingTarget $target, int $targetGid, int $targetLevel): int
    {
        $maxLevel = TravianBuildingCatalog::maxLevelForGid($targetGid);

        if ($maxLevel === null || $targetLevel <= (int) $maxLevel) {
            return $targetLevel;
        }

        $target->forceFill([
            'target_level' => (int) $maxLevel,
        ])->save();

        return (int) $maxLevel;
    }

    /**
     * @param  list<array{target: VillageBuildingTarget, current_slot: VillageBuilding, target_gid: int, mode: 'upgrade'|'construct'}>  $candidates
     * @param  array<string, true>  $candidateKeys
     * @param  array{target: VillageBuildingTarget, current_slot: VillageBuilding, target_gid: int, mode: 'upgrade'|'construct'}  $candidate
     */
    protected function appendBuildingCandidate(array &$candidates, array &$candidateKeys, array $candidate): void
    {
        $village = $candidate['current_slot']->village ?? null;
        if ($village instanceof Village && $this->candidateRecentlyBlockedByBuildPage($village, $candidate, 'building')) {
            return;
        }

        $key = (int) $candidate['current_slot']->slot_id.':'.(int) $candidate['target_gid'].':'.$candidate['mode'];

        if (isset($candidateKeys[$key])) {
            return;
        }

        $candidateKeys[$key] = true;
        $candidates[] = $candidate;
    }

    protected function resolveTargetGid(VillageBuildingTarget $target): int
    {
        $targetGid = (int) $target->building_gid;

        if ($targetGid !== 0) {
            return $targetGid;
        }

        $targetGid = TravianBuildingCatalog::gidForName($target->building_type) ?? 0;

        if ($targetGid > 0) {
            $target->forceFill([
                'building_gid' => $targetGid,
            ])->save();
        }

        return $targetGid;
    }

    /**
     * Execute a field upgrade candidate.
     *
     * @param  array{slot: VillageBuilding, field_key: string}  $candidate
     * @return array{executed: bool, blocked_reason: string|null, resource_shortage: array{payload: array<string, mixed>, analysis: BuildPageAnalysis}|null}
     */
    protected function executeFieldCandidate(Account $account, Village $village, AccountSession $session, array $candidate): array
    {
        $slot = $candidate['slot'];
        $buildPageUri = (string) config('travian.paths.build', '/build.php')
            .'?id='.(int) $slot->slot_id;
        $resolvedAction = $this->resolveActionUri(
            $session,
            $buildPageUri,
            $this->absoluteUri((string) config('travian.paths.overview', '/dorf1.php'), $account),
        );

        if ($resolvedAction === null) {
            return [
                'executed' => false,
                'blocked_reason' => null,
                'resource_shortage' => null,
            ];
        }

        $payload = [
            'queue_kind' => 'field',
            'slot_id' => (int) $slot->slot_id,
            'building_gid' => (int) $slot->building_gid,
            'building_name' => $slot->building_type,
            'current_level' => (int) $slot->current_level,
            'target_level' => (int) $slot->current_level + 1,
            'build_page_uri' => $buildPageUri,
            'build_effective_uri' => $resolvedAction['build_effective_uri'],
            'field_key' => $candidate['field_key'],
            'schedule_key' => $this->fieldCandidateScheduleKey($candidate),
        ];

        if ($resolvedAction['action_uri'] === null) {
            $this->recordResourceShortageCandidate($village, $payload, $resolvedAction['analysis']);

            if ($resolvedAction['analysis']->blockedReason !== 'crop_field_required' || $candidate['field_key'] === 'crop') {
                $this->recordBuildPageBlockedCandidate($account, $village, $payload, $resolvedAction['analysis']);
            }

            return [
                'executed' => false,
                'blocked_reason' => $resolvedAction['analysis']->blockedReason,
                'resource_shortage' => $resolvedAction['analysis']->isResourceShortage()
                    ? [
                        'payload' => $payload,
                        'analysis' => $resolvedAction['analysis'],
                    ]
                    : null,
            ];
        }

        $this->performBuildAction(
            account: $account,
            village: $village,
            session: $session,
            actionUri: $resolvedAction['action_uri'],
            payload: $payload,
            successMessage: 'Field upgrade order issued successfully.',
        );

        return [
            'executed' => true,
            'blocked_reason' => null,
            'resource_shortage' => null,
        ];
    }

    /**
     * Execute a building upgrade or construction candidate.
     *
     * @param  array{
     *     target: VillageBuildingTarget,
     *     current_slot: VillageBuilding,
     *     target_gid: int,
     *     mode: 'upgrade'|'construct'
     * }  $candidate
     * @return array{executed: bool, resource_shortage: array{payload: array<string, mixed>, analysis: BuildPageAnalysis}|null}
     */
    protected function executeBuildingCandidate(
        Account $account,
        Village $village,
        AccountSession $session,
        array $candidate,
        string $villageCenterReferer,
    ): array {
        $target = $candidate['target'];
        $currentSlot = $candidate['current_slot'];
        $targetGid = $candidate['target_gid'];
        $buildPageUri = (string) config('travian.paths.build', '/build.php')
            .'?id='.(int) $currentSlot->slot_id;

        if ($candidate['mode'] === 'upgrade') {
            $buildPageUri .= '&gid='.$targetGid;
        } else {
            $buildCategory = TravianBuildingCatalog::buildCategoryForGid($targetGid);

            if ($buildCategory === null) {
                return [
                    'executed' => false,
                    'resource_shortage' => null,
                ];
            }

            $buildPageUri .= '&category='.$buildCategory;
        }

        $resolvedAction = $this->resolveActionUri(
            $session,
            $buildPageUri,
            $villageCenterReferer,
            $candidate['mode'] === 'construct' ? $targetGid : null,
        );

        if ($resolvedAction === null) {
            return [
                'executed' => false,
                'resource_shortage' => null,
            ];
        }

        $payload = [
            'queue_kind' => 'building',
            'slot_id' => (int) $currentSlot->slot_id,
            'building_gid' => $targetGid,
            'building_name' => $target->building_type ?? TravianBuildingCatalog::nameForGid($targetGid),
            'current_level' => (int) $currentSlot->current_level,
            'target_level' => $candidate['mode'] === 'construct'
                ? 1
                : (int) $currentSlot->current_level + 1,
            'final_target_level' => (int) $target->target_level,
            'mode' => $candidate['mode'],
            'build_page_uri' => $buildPageUri,
            'build_effective_uri' => $resolvedAction['build_effective_uri'],
            'schedule_key' => $this->buildingCandidateScheduleKey($candidate),
        ];

        if ($resolvedAction['action_uri'] === null) {
            $this->recordResourceShortageCandidate($village, $payload, $resolvedAction['analysis']);
            $this->recordBuildPageBlockedCandidate($account, $village, $payload, $resolvedAction['analysis']);

            return [
                'executed' => false,
                'resource_shortage' => $resolvedAction['analysis']->isResourceShortage()
                    ? [
                        'payload' => $payload,
                        'analysis' => $resolvedAction['analysis'],
                    ]
                    : null,
            ];
        }

        $this->performBuildAction(
            account: $account,
            village: $village,
            session: $session,
            actionUri: $resolvedAction['action_uri'],
            payload: $payload,
            successMessage: $candidate['mode'] === 'construct'
                ? 'Building construction order issued successfully.'
                : 'Building upgrade order issued successfully.',
        );

        return [
            'executed' => true,
            'resource_shortage' => null,
        ];
    }

    /**
     * Resolve a clickable construction action URI from a build page.
     *
     * @return array{action_uri: string|null, build_effective_uri: string, analysis: BuildPageAnalysis}|null
     */
    protected function resolveActionUri(
        AccountSession $session,
        string $buildPageUri,
        string $referer,
        ?int $targetGid = null,
    ): ?array {
        $response = $session->get($buildPageUri, $this->documentRequestOptions($referer));

        if (! $response->successful()) {
            return null;
        }

        $analysis = $this->buildPageAnalyzer->analyze($response->body, $targetGid);

        return [
            'action_uri' => $analysis->actionUri,
            'build_effective_uri' => $response->effectiveUri,
            'analysis' => $analysis,
        ];
    }

    /**
     * Perform the final build request and persist a user-facing activity log.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function performBuildAction(
        Account $account,
        Village $village,
        AccountSession $session,
        string $actionUri,
        array $payload,
        string $successMessage,
    ): void {
        $response = $session->get($actionUri, $this->documentRequestOptions($this->absoluteUri((string) ($payload['build_effective_uri'] ?? $payload['build_page_uri']), $account)));

        if (! $response->successful() || ! $this->travianLoginAction->isAuthenticatedHtml($response->body)) {
            throw new \RuntimeException('Travian rejected the construction action or returned an unauthenticated page.');
        }

        $refreshResult = $this->refreshVillageSnapshot($account, $village, $session, $payload, $response);
        $payload = $refreshResult['payload'];
        $this->clearConstructionResourceShortages($village, (string) ($payload['queue_kind'] ?? ''));

        $result = [
            'action_uri' => $actionUri,
            'effective_uri' => $response->effectiveUri,
            ...$refreshResult['result'],
        ];

        ActivityLog::query()->create([
            'account_id' => $account->id,
            'village_id' => $village->id,
            'activity_type' => ActivityType::Build,
            'status' => ActivityLogStatus::Done,
            'payload' => $payload,
            'result' => $result,
            'message' => $successMessage,
            'executed_at' => now(),
        ]);
    }

    /**
     * Try to fill the first shortage from hero inventory, then immediately press the build action.
     *
     * @param  array{payload: array<string, mixed>, analysis: BuildPageAnalysis}  $resourceShortage
     */
    protected function retryConstructionAfterHeroResources(
        Account $account,
        Village $village,
        AccountSession $session,
        array $resourceShortage,
    ): bool {
        $payload = $resourceShortage['payload'];
        $analysis = $resourceShortage['analysis'];

        if (! $this->useHeroResourcesForConstruction->handle($account, $village, $session, $payload, $analysis)) {
            return false;
        }

        $buildPageUri = (string) ($payload['build_page_uri'] ?? '');

        if ($buildPageUri === '') {
            return false;
        }

        $reloadBuildPageUri = $this->appendReloadAuto($buildPageUri);
        $targetGid = (string) ($payload['queue_kind'] ?? '') === 'building'
            && (string) ($payload['mode'] ?? '') === 'construct'
            ? (int) ($payload['building_gid'] ?? 0)
            : null;
        $resolvedAction = $this->resolveActionUri(
            $session,
            $reloadBuildPageUri,
            $this->absoluteUri((string) ($payload['build_effective_uri'] ?? $buildPageUri), $account),
            $targetGid !== null && $targetGid > 0 ? $targetGid : null,
        );

        if ($resolvedAction === null) {
            return false;
        }

        $payload['build_page_uri'] = $reloadBuildPageUri;
        $payload['build_effective_uri'] = $resolvedAction['build_effective_uri'];

        if ($resolvedAction['action_uri'] === null) {
            $this->recordResourceShortageCandidate($village, $payload, $resolvedAction['analysis']);
            $this->recordBuildPageBlockedCandidate($account, $village, $payload, $resolvedAction['analysis']);

            return false;
        }

        $this->performBuildAction(
            account: $account,
            village: $village,
            session: $session,
            actionUri: $resolvedAction['action_uri'],
            payload: $payload,
            successMessage: $this->successMessageForConstructionPayload($payload),
        );

        return true;
    }

    protected function appendReloadAuto(string $uri): string
    {
        if (preg_match('/([?&])reload=[^&]*/', $uri) === 1) {
            return preg_replace('/([?&])reload=[^&]*/', '$1reload=auto', $uri) ?? $uri;
        }

        return $uri.(str_contains($uri, '?') ? '&' : '?').'reload=auto';
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function successMessageForConstructionPayload(array $payload): string
    {
        if (($payload['queue_kind'] ?? null) === 'field') {
            return 'Field upgrade order issued successfully.';
        }

        return ($payload['mode'] ?? null) === 'construct'
            ? 'Building construction order issued successfully.'
            : 'Building upgrade order issued successfully.';
    }

    /**
     * Refresh the affected village snapshot after a successful build request.
     *
     * @param  array<string, mixed>  $payload
     * @return array{
     *     payload: array<string, mixed>,
     *     result: array<string, mixed>
     * }
     */
    protected function refreshVillageSnapshot(
        Account $account,
        Village $village,
        AccountSession $session,
        array $payload,
        SessionResponse $actionResponse,
    ): array {
        try {
            [$dorf1Overview, $dorf2Overview, $result] = $this->resolvePostBuildSnapshot(
                account: $account,
                village: $village,
                session: $session,
                payload: $payload,
                actionResponse: $actionResponse,
            );

            $this->persistVillageOverview->handle($village->fresh(), $dorf1Overview->activeVillage, $dorf1Overview, $dorf2Overview);

            $matchedEntry = $this->matchConstructionEntry($dorf1Overview->runtimeState->constructionEntries, $payload);

            if ($matchedEntry instanceof ParsedConstructionQueueEntry) {
                $payload['remaining_seconds'] = $matchedEntry->remainingSeconds;
                $payload['remaining_label'] = $matchedEntry->remainingLabel;
                $payload['finish_label'] = $matchedEntry->finishLabel;
            }

            return [
                'payload' => $payload,
                'result' => $result,
            ];
        } catch (Throwable $throwable) {
            $this->recordFallbackConstructionState($village, $payload);

            return [
                'payload' => $payload,
                'result' => [
                    'overview_refreshed' => false,
                    'refresh_error' => $throwable->getMessage(),
                ],
            ];
        }
    }

    /**
     * Resolve the smallest useful post-build snapshot from the final response first.
     *
     * @param  array<string, mixed>  $payload
     * @return array{0: ParsedDorf1Overview, 1: ParsedDorf2Overview, 2: array<string, mixed>}
     */
    protected function resolvePostBuildSnapshot(
        Account $account,
        Village $village,
        AccountSession $session,
        array $payload,
        SessionResponse $actionResponse,
    ): array {
        $queueKind = (string) ($payload['queue_kind'] ?? '');

        if ($queueKind === 'field' && str_contains($actionResponse->body, 'body class="village1')) {
            try {
                $dorf1Overview = $this->dorf1OverviewParser->parse($actionResponse->body);

                return [
                    $dorf1Overview,
                    $this->buildCurrentDorf2Overview($village),
                    [
                        'overview_refreshed' => true,
                        'refresh_strategy' => 'action_response_dorf1',
                        'dorf1_effective_uri' => $actionResponse->effectiveUri,
                        'dorf2_effective_uri' => null,
                    ],
                ];
            } catch (Throwable) {
            }
        }

        if ($queueKind === 'building' && str_contains($actionResponse->body, 'body class="village2')) {
            try {
                $dorf2Overview = $this->dorf2OverviewParser->parse($actionResponse->body);
                $dorf1Response = $session->get(
                    (string) config('travian.paths.overview', '/dorf1.php'),
                    $this->documentRequestOptions($actionResponse->effectiveUri),
                );
                $dorf1Overview = $this->dorf1OverviewParser->parse($dorf1Response->body);

                return [
                    $dorf1Overview,
                    $dorf2Overview,
                    [
                        'overview_refreshed' => true,
                        'refresh_strategy' => 'action_response_dorf2_plus_dorf1',
                        'dorf1_effective_uri' => $dorf1Response->effectiveUri,
                        'dorf2_effective_uri' => $actionResponse->effectiveUri,
                    ],
                ];
            } catch (Throwable) {
            }
        }

        $dorf1Response = $session->get(
            (string) config('travian.paths.overview', '/dorf1.php'),
            $this->documentRequestOptions($actionResponse->effectiveUri),
        );
        $dorf1Overview = $this->dorf1OverviewParser->parse($dorf1Response->body);
        $dorf2Response = $session->get(
            (string) config('travian.paths.village_center', '/dorf2.php'),
            $this->documentRequestOptions($dorf1Response->effectiveUri),
        );
        $dorf2Overview = $this->dorf2OverviewParser->parse($dorf2Response->body);

        return [
            $dorf1Overview,
            $dorf2Overview,
            [
                'overview_refreshed' => true,
                'refresh_strategy' => 'fallback_full_refresh',
                'dorf1_effective_uri' => $dorf1Response->effectiveUri,
                'dorf2_effective_uri' => $dorf2Response->effectiveUri,
            ],
        ];
    }

    protected function buildCurrentDorf2Overview(Village $village): ParsedDorf2Overview
    {
        return new ParsedDorf2Overview(
            buildingSlots: $village->buildings
                ->filter(static fn (VillageBuilding $slot): bool => $slot->slot_id >= 19 && $slot->slot_id <= 40)
                ->sortBy('slot_id')
                ->map(static fn (VillageBuilding $slot): ParsedVillageSlot => new ParsedVillageSlot(
                    slotId: (int) $slot->slot_id,
                    buildingGid: (int) $slot->building_gid,
                    buildingName: $slot->building_type,
                    currentLevel: (int) $slot->current_level,
                    kind: 'building',
                    isEmpty: (int) $slot->building_gid === 0,
                ))
                ->values()
                ->all(),
        );
    }

    /**
     * Build headers for top-level document navigation requests.
     *
     * @return array<string, mixed>
     */
    protected function documentRequestOptions(?string $referer = null): array
    {
        $headers = [
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
            'Cache-Control' => 'no-cache',
            'Pragma' => 'no-cache',
            'Sec-Fetch-Dest' => 'document',
            'Sec-Fetch-Mode' => 'navigate',
            'Sec-Fetch-Site' => 'same-origin',
            'Sec-Fetch-User' => '?1',
            'Upgrade-Insecure-Requests' => '1',
        ];

        if ($referer !== null && $referer !== '') {
            $headers['Referer'] = $referer;
        }

        return [
            'headers' => $headers,
            'allow_redirects' => [
                'max' => 5,
                'strict' => false,
                'referer' => true,
                'protocols' => ['http', 'https'],
                'track_redirects' => false,
            ],
        ];
    }

    protected function absoluteUri(string $uri, Account $account): string
    {
        if (preg_match('/^https?:\/\//i', $uri) === 1) {
            return $uri;
        }

        return rtrim($account->server_url, '/').'/'.ltrim($uri, '/');
    }

    /**
     * Match the freshly queued construction entry back onto the issued action payload.
     *
     * @param  list<ParsedConstructionQueueEntry>  $constructionEntries
     * @param  array<string, mixed>  $payload
     */
    protected function matchConstructionEntry(array $constructionEntries, array $payload): ?ParsedConstructionQueueEntry
    {
        $buildingName = trim((string) ($payload['building_name'] ?? ''));
        $targetLevel = isset($payload['target_level']) ? (int) $payload['target_level'] : null;

        foreach ($constructionEntries as $constructionEntry) {
            if (! $constructionEntry instanceof ParsedConstructionQueueEntry) {
                continue;
            }

            if (
                $buildingName !== ''
                && $constructionEntry->buildingName === $buildingName
                && ($targetLevel === null || $constructionEntry->targetLevel === $targetLevel)
            ) {
                return $constructionEntry;
            }
        }

        return $constructionEntries[0] ?? null;
    }

    /**
     * Keep a minimal local queue hint when live refresh fails.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function recordFallbackConstructionState(Village $village, array $payload): void
    {
        $runtimeState = $village->runtimeState;

        if ($runtimeState === null) {
            return;
        }

        $constructionEntries = is_array($runtimeState->construction_entries)
            ? array_values($runtimeState->construction_entries)
            : [];

        $newEntry = [
            'building_name' => $payload['building_name'] ?? 'Building',
            'target_level' => isset($payload['target_level']) ? (int) $payload['target_level'] : null,
            'remaining_seconds' => null,
            'remaining_label' => 'Queued now',
            'finish_label' => null,
            'recorded_at' => now()->toIso8601String(),
        ];

        $constructionEntries = array_values(array_filter(
            $constructionEntries,
            static fn (mixed $entry): bool => ! is_array($entry)
                || ($entry['building_name'] ?? null) !== $newEntry['building_name']
                || (int) ($entry['target_level'] ?? 0) !== (int) ($newEntry['target_level'] ?? 0),
        ));

        array_unshift($constructionEntries, $newEntry);

        $runtimeState->forceFill([
            'construction_entries' => array_slice($constructionEntries, 0, 6),
            'server_reported_at' => now(),
        ])->save();

        $village->buildings()
            ->where('slot_id', (int) ($payload['slot_id'] ?? 0))
            ->update([
                'is_under_construction' => true,
                'finish_at' => null,
            ]);
    }

    /**
     * Remember that a candidate was valid but blocked by missing resources.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function recordResourceShortageCandidate(Village $village, array $payload, BuildPageAnalysis $analysis): void
    {
        if (! $analysis->isResourceShortage()) {
            return;
        }

        $runtimeState = $village->runtimeState;

        if ($runtimeState === null) {
            return;
        }

        $resourceReadyAt = $analysis->resourceReadySeconds !== null
            ? now()->addSeconds($analysis->resourceReadySeconds)
            : null;

        $newEntry = [
            'queue_kind' => (string) ($payload['queue_kind'] ?? ''),
            'slot_id' => isset($payload['slot_id']) ? (int) $payload['slot_id'] : null,
            'building_gid' => isset($payload['building_gid']) ? (int) $payload['building_gid'] : null,
            'building_name' => $payload['building_name'] ?? null,
            'current_level' => isset($payload['current_level']) ? (int) $payload['current_level'] : null,
            'target_level' => isset($payload['target_level']) ? (int) $payload['target_level'] : null,
            'final_target_level' => isset($payload['final_target_level']) ? (int) $payload['final_target_level'] : null,
            'mode' => $payload['mode'] ?? null,
            'field_key' => $payload['field_key'] ?? null,
            'schedule_key' => $payload['schedule_key'] ?? null,
            'build_page_uri' => $payload['build_page_uri'] ?? null,
            'build_effective_uri' => $payload['build_effective_uri'] ?? null,
            'required_resources' => $analysis->requiredResources,
            'blocked_reason' => $analysis->blockedReason,
            'block_message' => $analysis->blockedMessage,
            'resource_ready_seconds' => $analysis->resourceReadySeconds,
            'resource_ready_label' => $analysis->resourceReadyLabel,
            'resource_ready_at' => $resourceReadyAt?->toISOString(),
            'recorded_at' => now()->toISOString(),
        ];

        $entries = is_array($runtimeState->construction_resource_shortages)
            ? array_values($runtimeState->construction_resource_shortages)
            : [];

        $entries = array_values(array_filter(
            $entries,
            static fn (mixed $entry): bool => ! is_array($entry)
                || ($entry['queue_kind'] ?? null) !== $newEntry['queue_kind']
                || (int) ($entry['slot_id'] ?? 0) !== (int) ($newEntry['slot_id'] ?? 0)
                || (int) ($entry['building_gid'] ?? 0) !== (int) ($newEntry['building_gid'] ?? 0)
                || (int) ($entry['target_level'] ?? 0) !== (int) ($newEntry['target_level'] ?? 0),
        ));

        $entries[] = $newEntry;

        usort($entries, static function (array $left, array $right): int {
            $leftReadyAt = (string) ($left['resource_ready_at'] ?? '');
            $rightReadyAt = (string) ($right['resource_ready_at'] ?? '');

            return $leftReadyAt <=> $rightReadyAt;
        });

        $runtimeState->forceFill([
            'construction_resource_shortages' => array_slice($entries, 0, 10),
        ])->save();
    }

    /**
     * Clear stale shortage hints once a construction action succeeds.
     */
    protected function clearConstructionResourceShortages(Village $village, string $queueKind): void
    {
        $runtimeState = $village->fresh('runtimeState')->runtimeState;

        if ($runtimeState === null) {
            return;
        }

        $entries = is_array($runtimeState->construction_resource_shortages)
            ? array_values(array_filter(
                $runtimeState->construction_resource_shortages,
                static fn (mixed $entry): bool => ! is_array($entry) || ($entry['queue_kind'] ?? null) !== $queueKind,
            ))
            : [];

        $runtimeState->forceFill([
            'construction_resource_shortages' => $entries,
        ])->save();
    }

    /**
     * Log a locally blocked building target once the catalog rules reject it.
     */
    protected function recordBlockedBuildingCandidate(
        Account $account,
        Village $village,
        VillageBuildingTarget $target,
        BuildingEligibility $eligibility,
    ): void {
        if ($this->buildingRulesBlockAlreadyLogged($village, $target)) {
            return;
        }

        ActivityLog::query()->create([
            'account_id' => $account->id,
            'village_id' => $village->id,
            'activity_type' => ActivityType::Build,
            'status' => ActivityLogStatus::Pending,
            'payload' => [
                'queue_kind' => 'building',
                'slot_id' => (int) $target->slot_id,
                'building_gid' => (int) $target->building_gid,
                'building_name' => $target->building_type,
                'target_level' => (int) $target->target_level,
                'blocked_reason' => $eligibility->blockedReason,
                'missing_requirements' => $eligibility->missingRequirements,
                'required_resources' => $eligibility->requiredResources,
            ],
            'message' => 'Building candidate blocked by construction rules.',
            'executed_at' => now(),
        ]);
    }

    protected function buildingRulesBlockAlreadyLogged(Village $village, VillageBuildingTarget $target): bool
    {
        return ActivityLog::query()
            ->where('village_id', $village->id)
            ->where('activity_type', ActivityType::Build)
            ->where('status', ActivityLogStatus::Pending)
            ->where('message', 'Building candidate blocked by construction rules.')
            ->where('executed_at', '>=', now()->subMinutes($this->buildPageBlockCooldownMinutes()))
            ->where('payload->slot_id', (int) $target->slot_id)
            ->where('payload->building_gid', (int) $target->building_gid)
            ->where('payload->target_level', (int) $target->target_level)
            ->exists();
    }

    /**
     * Log non-resource build page blocks, such as unmet server-rendered prerequisites.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function recordBuildPageBlockedCandidate(Account $account, Village $village, array $payload, BuildPageAnalysis $analysis): void
    {
        if ($analysis->isResourceShortage() || $analysis->blockedReason === null) {
            return;
        }

        if (($payload['queue_kind'] ?? null) === 'field') {
            return;
        }

        if ($this->buildPageBlockAlreadyLogged($village, $payload)) {
            return;
        }

        ActivityLog::query()->create([
            'account_id' => $account->id,
            'village_id' => $village->id,
            'activity_type' => ActivityType::Build,
            'status' => ActivityLogStatus::Pending,
            'payload' => [
                ...$payload,
                'blocked_reason' => $analysis->blockedReason,
                'blocked_message' => $analysis->blockedMessage,
                'missing_requirements' => $analysis->missingRequirements,
                'required_resources' => $analysis->requiredResources,
            ],
            'message' => 'Construction candidate blocked by build page.',
            'executed_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $candidate
     */
    protected function candidateRecentlyBlockedByBuildPage(Village $village, array $candidate, string $queueKind): bool
    {
        return ActivityLog::query()
            ->where('village_id', $village->id)
            ->where('activity_type', ActivityType::Build)
            ->where('status', ActivityLogStatus::Pending)
            ->where('message', 'Construction candidate blocked by build page.')
            ->where('executed_at', '>=', now()->subMinutes($this->buildPageBlockCooldownMinutes()))
            ->where('payload->schedule_key', $this->candidateScheduleKey($candidate, $queueKind))
            ->exists();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function buildPageBlockAlreadyLogged(Village $village, array $payload): bool
    {
        $scheduleKey = (string) ($payload['schedule_key'] ?? '');

        if ($scheduleKey === '') {
            return false;
        }

        return ActivityLog::query()
            ->where('village_id', $village->id)
            ->where('activity_type', ActivityType::Build)
            ->where('status', ActivityLogStatus::Pending)
            ->where('message', 'Construction candidate blocked by build page.')
            ->where('executed_at', '>=', now()->subMinutes($this->buildPageBlockCooldownMinutes()))
            ->where('payload->schedule_key', $scheduleKey)
            ->exists();
    }

    protected function buildPageBlockCooldownMinutes(): int
    {
        return max(1, (int) config('travian.automation.build_page_block_cooldown_minutes', 10));
    }

    /**
     * Move dashboard-pinned schedule entries to the front without changing the natural order of everything else.
     *
     * @template T of array<string, mixed>
     *
     * @param  list<T>  $candidates
     * @return list<T>
     */
    protected function applySchedulePreferences(array $candidates, VillageSetting $settings, string $queueKind): array
    {
        if ($candidates === []) {
            return [];
        }

        $pinnedKeys = $this->constructionSchedulePreferences($settings)['pinned'];

        if ($pinnedKeys === []) {
            return $candidates;
        }

        $pinnedPositions = array_flip($pinnedKeys);
        $indexedCandidates = array_map(
            fn (array $candidate, int $index): array => [
                'candidate' => $candidate,
                'index' => $index,
                'pinned_position' => $this->candidatePinnedPosition($candidate, $queueKind, $pinnedPositions) ?? PHP_INT_MAX,
            ],
            $candidates,
            array_keys($candidates),
        );

        usort($indexedCandidates, static function (array $left, array $right): int {
            if ($left['pinned_position'] !== $right['pinned_position']) {
                return $left['pinned_position'] <=> $right['pinned_position'];
            }

            return $left['index'] <=> $right['index'];
        });

        return array_values(array_map(
            static fn (array $row): array => $row['candidate'],
            $indexedCandidates,
        ));
    }

    /**
     * Keep execution aligned with the compact dashboard schedule window.
     *
     * @param  list<array<string, mixed>>  $fieldCandidates
     * @param  list<array<string, mixed>>  $buildingCandidates
     * @return list<string>
     */
    protected function visibleScheduleKeys(array $fieldCandidates, array $buildingCandidates, VillageSetting $settings): array
    {
        $rows = [];

        foreach ($fieldCandidates as $candidate) {
            $rows[] = [
                'key' => $this->candidateScheduleKey($candidate, 'field'),
                'keys' => $this->candidateScheduleKeys($candidate, 'field'),
                'kind' => 'field',
                'priority' => $this->candidatePriority($candidate, $settings, 'field'),
            ];
        }

        foreach ($buildingCandidates as $candidate) {
            $rows[] = [
                'key' => $this->candidateScheduleKey($candidate, 'building'),
                'keys' => $this->candidateScheduleKeys($candidate, 'building'),
                'kind' => 'building',
                'priority' => $this->candidatePriority($candidate, $settings, 'building'),
            ];
        }

        $pinnedPositions = array_flip($this->constructionSchedulePreferences($settings)['pinned']);

        $orderedRows = collect($rows)
            ->values()
            ->map(static fn (array $row, int $index): array => [
                ...$row,
                'index' => $index,
                'kind_order' => $row['kind'] === 'building' ? 0 : 1,
                'pinned_position' => collect($row['keys'])
                    ->map(static fn (string $key): int => $pinnedPositions[$key] ?? PHP_INT_MAX)
                    ->min(),
            ])
            ->sortBy([
                ['pinned_position', 'asc'],
                ['priority', 'asc'],
                ['kind_order', 'asc'],
                ['index', 'asc'],
            ])
            ->values();

        $primaryRows = $orderedRows->take(8)->values();
        $visibleRows = $orderedRows->take(10)->values();

        if ($orderedRows->contains(static fn (array $row): bool => $row['kind'] === 'building')
            && $primaryRows->where('kind', 'building')->isEmpty()) {
            $primaryKeys = $primaryRows->pluck('key')->all();
            $buildingReserveRows = $orderedRows
                ->where('kind', 'building')
                ->reject(static fn (array $row): bool => in_array($row['key'], $primaryKeys, true))
                ->take(2)
                ->values();

            $visibleRows = $primaryRows->concat($buildingReserveRows)->take(10)->values();
        }

        return $visibleRows->pluck('key')->values()->all();
    }

    /**
     * @template T of array<string, mixed>
     *
     * @param  list<T>  $candidates
     * @param  list<string>  $visibleScheduleKeys
     * @return list<T>
     */
    protected function filterCandidatesByScheduleKeys(array $candidates, array $visibleScheduleKeys, string $queueKind): array
    {
        if ($candidates === [] || $visibleScheduleKeys === []) {
            return [];
        }

        $visibleLookup = array_flip($visibleScheduleKeys);

        return array_values(array_filter(
            $candidates,
            fn (array $candidate): bool => $this->candidateHasAnyScheduleKey($candidate, $queueKind, $visibleLookup),
        ));
    }

    /**
     * Let dashboard pins choose between the Roman field and building lanes.
     *
     * @param  list<array<string, mixed>>  $fieldCandidates
     * @param  list<array<string, mixed>>  $buildingCandidates
     * @return list<'field'|'building'>
     */
    protected function resolveRomanQueueOrder(array $fieldCandidates, array $buildingCandidates, VillageSetting $settings): array
    {
        return $this->resolveSingleQueueOrder($fieldCandidates, $buildingCandidates, $settings);
    }

    /**
     * Let dashboard pins choose between field and building lanes.
     *
     * @param  list<array<string, mixed>>  $fieldCandidates
     * @param  list<array<string, mixed>>  $buildingCandidates
     * @return list<'field'|'building'>
     */
    protected function resolveSingleQueueOrder(array $fieldCandidates, array $buildingCandidates, VillageSetting $settings): array
    {
        $bestFieldPin = $this->firstCandidatePinnedPosition($fieldCandidates, $settings, 'field');
        $bestBuildingPin = $this->firstCandidatePinnedPosition($buildingCandidates, $settings, 'building');

        if ($bestBuildingPin !== null && ($bestFieldPin === null || $bestBuildingPin < $bestFieldPin)) {
            return ['building', 'field'];
        }

        if ($bestFieldPin === null && $bestBuildingPin === null && $this->hasEssentialMainBuildingRepairCandidate($buildingCandidates)) {
            return ['building', 'field'];
        }

        return ['field', 'building'];
    }

    /**
     * @param  list<array<string, mixed>>  $buildingCandidates
     */
    protected function hasEssentialMainBuildingRepairCandidate(array $buildingCandidates): bool
    {
        return collect($buildingCandidates)->contains(static function (array $candidate): bool {
            $currentSlot = $candidate['current_slot'] ?? null;

            return $currentSlot instanceof VillageBuilding
                && (int) ($candidate['target_gid'] ?? 0) === 15
                && (int) $currentSlot->slot_id === 26
                && (int) $currentSlot->building_gid === 0
                && (int) $currentSlot->current_level === 0;
        });
    }

    /**
     * @param  array<string, mixed>  $candidate
     */
    protected function candidatePriority(array $candidate, VillageSetting $settings, string $queueKind): int
    {
        if ($queueKind === 'building') {
            $target = $candidate['target'] ?? null;

            return $target instanceof VillageBuildingTarget ? (int) $target->priority : 999;
        }

        $fieldKey = $candidate['field_key'] ?? null;

        if (! is_string($fieldKey) || $fieldKey === '') {
            return 999;
        }

        return (int) ($this->resolveEffectiveFieldPriority($settings)[$fieldKey] ?? 999);
    }

    /**
     * Find the first dashboard pin position that matches the supplied candidates.
     *
     * @param  list<array<string, mixed>>  $candidates
     */
    protected function firstCandidatePinnedPosition(array $candidates, VillageSetting $settings, string $queueKind): ?int
    {
        $pinnedKeys = $this->constructionSchedulePreferences($settings)['pinned'];

        if ($pinnedKeys === [] || $candidates === []) {
            return null;
        }

        $pinnedPositions = array_flip($pinnedKeys);
        $bestPosition = null;

        foreach ($candidates as $candidate) {
            $position = $this->candidatePinnedPosition($candidate, $queueKind, $pinnedPositions);

            if ($position === null) {
                continue;
            }

            $bestPosition = $bestPosition === null ? $position : min($bestPosition, $position);
        }

        return $bestPosition;
    }

    /**
     * Determine whether this candidate is marked as a stop point in the dashboard schedule.
     *
     * @param  array<string, mixed>  $candidate
     */
    protected function candidateIsHeld(array $candidate, VillageSetting $settings, string $queueKind): bool
    {
        $preferences = $this->constructionSchedulePreferences($settings);

        foreach ($this->candidateScheduleKeys($candidate, $queueKind) as $scheduleKey) {
            if (in_array($scheduleKey, $preferences['held'], true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{pinned: list<string>, held: list<string>}
     */
    protected function constructionSchedulePreferences(VillageSetting $settings): array
    {
        $preferences = is_array($settings->construction_schedule)
            ? $settings->construction_schedule
            : [];

        return [
            'pinned' => $this->normalizeScheduleKeys($preferences['pinned'] ?? []),
            'held' => $this->normalizeScheduleKeys($preferences['held'] ?? []),
        ];
    }

    /**
     * @return list<string>
     */
    protected function normalizeScheduleKeys(mixed $keys): array
    {
        if (! is_array($keys)) {
            return [];
        }

        return array_values(array_unique(array_filter(
            array_map(static fn (mixed $key): string => is_scalar($key) ? (string) $key : '', $keys),
            static fn (string $key): bool => $key !== '',
        )));
    }

    /**
     * @param  array<string, mixed>  $candidate
     */
    protected function candidateScheduleKey(array $candidate, string $queueKind): string
    {
        return $this->candidateScheduleKeys($candidate, $queueKind)[0];
    }

    /**
     * Return the primary schedule key followed by legacy aliases that should still match saved preferences.
     *
     * @param  array<string, mixed>  $candidate
     * @return list<string>
     */
    protected function candidateScheduleKeys(array $candidate, string $queueKind): array
    {
        if ($queueKind === 'field') {
            return [$this->fieldCandidateScheduleKey($candidate)];
        }

        return [
            $this->buildingTargetScheduleKey($candidate),
            $this->legacyBuildingCandidateScheduleKey($candidate),
        ];
    }

    /**
     * @param  array<string, int>  $pinnedPositions
     */
    protected function candidatePinnedPosition(array $candidate, string $queueKind, array $pinnedPositions): ?int
    {
        $positions = [];

        foreach ($this->candidateScheduleKeys($candidate, $queueKind) as $scheduleKey) {
            if (array_key_exists($scheduleKey, $pinnedPositions)) {
                $positions[] = $pinnedPositions[$scheduleKey];
            }
        }

        return $positions === [] ? null : min($positions);
    }

    /**
     * @param  array<string, int>  $scheduleKeyLookup
     */
    protected function candidateHasAnyScheduleKey(array $candidate, string $queueKind, array $scheduleKeyLookup): bool
    {
        foreach ($this->candidateScheduleKeys($candidate, $queueKind) as $scheduleKey) {
            if (isset($scheduleKeyLookup[$scheduleKey])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array{slot: VillageBuilding, field_key: string}  $candidate
     */
    protected function fieldCandidateScheduleKey(array $candidate): string
    {
        $slot = $candidate['slot'];

        return 'field:'.(int) $slot->slot_id.':'.((int) $slot->current_level + 1);
    }

    /**
     * @param  array{
     *     target: VillageBuildingTarget,
     *     current_slot: VillageBuilding,
     *     target_gid: int,
     *     mode: 'upgrade'|'construct'
     * }  $candidate
     */
    protected function buildingCandidateScheduleKey(array $candidate): string
    {
        return $this->buildingTargetScheduleKey($candidate);
    }

    /**
     * @param  array{
     *     target: VillageBuildingTarget,
     *     current_slot: VillageBuilding,
     *     target_gid: int,
     *     mode: 'upgrade'|'construct'
     * }  $candidate
     */
    protected function buildingTargetScheduleKey(array $candidate): string
    {
        $currentSlot = $candidate['current_slot'];

        return 'building-target:'.(int) $currentSlot->slot_id.':'.(int) $candidate['target_gid'];
    }

    /**
     * @param  array{
     *     target: VillageBuildingTarget,
     *     current_slot: VillageBuilding,
     *     target_gid: int,
     *     mode: 'upgrade'|'construct'
     * }  $candidate
     */
    protected function legacyBuildingCandidateScheduleKey(array $candidate): string
    {
        $currentSlot = $candidate['current_slot'];
        $targetLevel = $candidate['mode'] === 'construct'
            ? 1
            : (int) $currentSlot->current_level + 1;

        return 'building:'.(int) $currentSlot->slot_id.':'.$targetLevel;
    }

    /**
     * Resolve the village priority source, allowing program-level inheritance.
     *
     * @return array{wood: int, clay: int, iron: int, crop: int}
     */
    protected function resolveEffectiveFieldPriority(VillageSetting $settings): array
    {
        if ((bool) $settings->inherit_from_account) {
            return SystemSetting::constructionDefaults()['field_priority'];
        }

        return $this->normalizeFieldPriority($settings->field_priority);
    }

    /**
     * Decide whether negative crop production should temporarily override field priority.
     */
    protected function shouldPrioritizeCropFieldsForNegativeProduction(Village $village, VillageSetting $settings): bool
    {
        if (! $this->resolvePrioritizeCropFieldsWhenNegative($settings)) {
            return false;
        }

        $village->loadMissing('resourceState');

        return (int) ($village->resourceState?->crop_production ?? 0) < 0;
    }

    /**
     * Resolve the effective negative-crop protection setting.
     */
    protected function resolvePrioritizeCropFieldsWhenNegative(VillageSetting $settings): bool
    {
        if ((bool) $settings->inherit_from_account) {
            return SystemSetting::constructionDefaults()['prioritize_crop_fields_when_negative'];
        }

        return (bool) $settings->prioritize_crop_fields_when_negative;
    }

    /**
     * Normalize field priorities, falling back to the village defaults.
     *
     * @param  array<string, mixed>|null  $fieldPriority
     * @return array{wood: int, clay: int, iron: int, crop: int}
     */
    protected function normalizeFieldPriority(?array $fieldPriority): array
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
     * Resolve the relative URI that switches the current session to the target village.
     */
    protected function resolveVillageSwitchUri(Village $village): string
    {
        return (string) config('travian.paths.overview', '/dorf1.php')
            .'?newdid='.rawurlencode((string) $village->travian_village_id);
    }
}
