<?php

namespace App\Application\Accounts\Construction;

use App\Application\Accounts\Construction\Data\BuildPageAnalysis;
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
        protected Dorf1OverviewParser $dorf1OverviewParser,
        protected Dorf2OverviewParser $dorf2OverviewParser,
        protected PersistVillageOverview $persistVillageOverview,
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

            if (TravianBuildingCatalog::isRomanTribe($tribeId)) {
                if ($fieldCandidates !== [] && $queueAvailability['field']) {
                    $this->executeFirstFieldCandidate($account, $village, $session, $fieldCandidates);
                }

                if ($buildingCandidates !== [] && $queueAvailability['building']) {
                    $this->executeFirstBuildingCandidate($account, $village, $session, $buildingCandidates, $switchResponse->effectiveUri);
                }

                return;
            }

            if ($fieldCandidates !== [] && $queueAvailability['field']) {
                $fieldWasExecuted = $this->executeFirstFieldCandidate($account, $village, $session, $fieldCandidates);

                if ($fieldWasExecuted) {
                    return;
                }
            }

            if ($buildingCandidates !== [] && $queueAvailability['building']) {
                $this->executeFirstBuildingCandidate($account, $village, $session, $buildingCandidates, $switchResponse->effectiveUri);
            }
        } catch (Throwable $throwable) {
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
        $fieldSlots = $village->buildings
            ->filter(static fn (VillageBuilding $slot): bool => $slot->slot_id >= 1 && $slot->slot_id <= 18 && $slot->building_gid >= 1 && $slot->building_gid <= 4)
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

            if ((int) $fieldSlot->current_level >= 10) {
                continue;
            }

            if (! $this->passesFieldPriorityLeadGuard($fieldSlot, $fieldKey, $fieldSlots, $priorityMap)) {
                continue;
            }

            $candidates[] = [
                'slot' => $fieldSlot,
                'field_key' => $fieldKey,
            ];
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
            $candidates = $this->prependCropRecoveryCandidates($village, $candidates);
        }

        return array_values($candidates);
    }

    /**
     * Move crop fields to the front when the village is burning crop.
     *
     * @param  list<array{slot: VillageBuilding, field_key: string}>  $candidates
     * @return list<array{slot: VillageBuilding, field_key: string}>
     */
    protected function prependCropRecoveryCandidates(Village $village, array $candidates): array
    {
        $cropCandidates = $this->selectCropRecoveryCandidates($village);

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
    protected function selectCropRecoveryCandidates(Village $village): array
    {
        $candidates = $village->buildings
            ->filter(static fn (VillageBuilding $slot): bool => $slot->slot_id >= 1
                && $slot->slot_id <= 18
                && (int) $slot->building_gid === 4
                && (int) $slot->current_level < 10)
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
        $maxLevelByField = [];

        foreach ($fieldSlots as $fieldSlot) {
            if (! $fieldSlot instanceof VillageBuilding) {
                continue;
            }

            $fieldKey = TravianBuildingCatalog::fieldKeyForGid((int) $fieldSlot->building_gid);

            if ($fieldKey === null) {
                continue;
            }

            $fieldLevel = (int) $fieldSlot->current_level;
            $maxLevelByField[$fieldKey] = isset($maxLevelByField[$fieldKey])
                ? max($maxLevelByField[$fieldKey], $fieldLevel)
                : $fieldLevel;
        }

        foreach ($maxLevelByField as $fieldKey => $maxLevel) {
            if ($fieldKey === $candidateFieldKey) {
                continue;
            }

            $otherPriority = $priorityMap[$fieldKey] ?? 999;

            if ($otherPriority === $candidatePriority) {
                $allowedLead = 0;
            } else {
                $allowedLead = abs($otherPriority - $candidatePriority);
            }

            if ($candidateNextLevel > ((int) $maxLevel + $allowedLead)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Try field candidates in order until one can actually issue a build action.
     *
     * @param  list<array{slot: VillageBuilding, field_key: string}>  $candidates
     */
    protected function executeFirstFieldCandidate(Account $account, Village $village, AccountSession $session, array $candidates): bool
    {
        $cropFallbackNeeded = false;

        foreach ($candidates as $candidate) {
            $result = $this->executeFieldCandidate($account, $village, $session, $candidate);

            if ($result['executed']) {
                return true;
            }

            if ($result['blocked_reason'] === 'crop_field_required') {
                $cropFallbackNeeded = true;
            }
        }

        if (! $cropFallbackNeeded) {
            return false;
        }

        foreach ($this->selectCropRecoveryCandidates($village) as $candidate) {
            $result = $this->executeFieldCandidate($account, $village, $session, $candidate);

            if ($result['executed']) {
                return true;
            }
        }

        return false;
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
     */
    protected function executeFirstBuildingCandidate(
        Account $account,
        Village $village,
        AccountSession $session,
        array $candidates,
        string $villageReferer,
    ): bool {
        $villageCenterResponse = $session->get(
            (string) config('travian.paths.village_center', '/dorf2.php'),
            $this->documentRequestOptions($villageReferer),
        );

        foreach ($candidates as $candidate) {
            if ($this->executeBuildingCandidate($account, $village, $session, $candidate, $villageCenterResponse->effectiveUri)) {
                return true;
            }
        }

        return false;
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

        foreach ($village->buildingTargets->sortBy('priority') as $target) {
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

            $targetSlotId = TravianBuildingCatalog::fixedSlotForGid(
                $targetGid,
                $village->runtimeState?->tribe_id !== null ? (int) $village->runtimeState->tribe_id : null,
            ) ?? (int) $target->slot_id;
            $currentSlot = $village->buildings->firstWhere('slot_id', $targetSlotId);

            if (! $currentSlot instanceof VillageBuilding) {
                continue;
            }

            $currentGid = (int) $currentSlot->building_gid;
            $currentLevel = (int) $currentSlot->current_level;

            if ($currentGid !== 0 && $currentGid !== $targetGid) {
                continue;
            }

            if ($currentGid === 0) {
                $eligibility = TravianBuildingCatalog::canConstructInVillage($targetGid, $account, $village);

                if (! $eligibility->allowed) {
                    $this->recordBlockedBuildingCandidate($account, $village, $target, $eligibility);

                    continue;
                }

                $candidates[] = [
                    'target' => $target,
                    'current_slot' => $currentSlot,
                    'target_gid' => $targetGid,
                    'mode' => 'construct',
                ];

                continue;
            }

            if ($currentLevel >= $targetLevel) {
                continue;
            }

            $candidates[] = [
                'target' => $target,
                'current_slot' => $currentSlot,
                'target_gid' => $targetGid,
                'mode' => 'upgrade',
            ];
        }

        return $candidates;
    }

    /**
     * Execute a field upgrade candidate.
     *
     * @param  array{slot: VillageBuilding, field_key: string}  $candidate
     * @return array{executed: bool, blocked_reason: string|null}
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
        ];

        if ($resolvedAction['action_uri'] === null) {
            $this->recordResourceShortageCandidate($village, $payload, $resolvedAction['analysis']);

            if ($resolvedAction['analysis']->blockedReason !== 'crop_field_required' || $candidate['field_key'] === 'crop') {
                $this->recordBuildPageBlockedCandidate($account, $village, $payload, $resolvedAction['analysis']);
            }

            return [
                'executed' => false,
                'blocked_reason' => $resolvedAction['analysis']->blockedReason,
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
     */
    protected function executeBuildingCandidate(
        Account $account,
        Village $village,
        AccountSession $session,
        array $candidate,
        string $villageCenterReferer,
    ): bool {
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
                return false;
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
            return false;
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
        ];

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
            successMessage: $candidate['mode'] === 'construct'
                ? 'Building construction order issued successfully.'
                : 'Building upgrade order issued successfully.',
        );

        return true;
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
