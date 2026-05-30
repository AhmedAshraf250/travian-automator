<?php

namespace App\Application\Accounts\Construction;

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
use App\Application\Travian\TravianBuildingCatalog;
use App\Enums\ActivityLogStatus;
use App\Enums\ActivityType;
use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\Village;
use App\Models\VillageBuilding;
use App\Models\VillageBuildingTarget;
use App\Models\VillageSetting;
use DOMDocument;
use DOMElement;
use DOMXPath;
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

            $session->get(
                $this->resolveVillageSwitchUri($village),
                $this->documentRequestOptions($this->absoluteUri((string) config('travian.paths.overview', '/dorf1.php'), $account)),
            );

            $queueAvailability = $this->resolveQueueAvailability(
                is_array($runtimeState->construction_entries) ? $runtimeState->construction_entries : [],
                $tribeId,
            );

            $fieldCandidates = ! $settings->pause_fields && $queueAvailability['field']
                ? $this->selectFieldCandidates($village, $settings)
                : [];
            $buildingCandidate = ! $settings->pause_buildings && $queueAvailability['building']
                ? $this->selectBuildingCandidate($village)
                : null;

            if (TravianBuildingCatalog::isRomanTribe($tribeId)) {
                if ($fieldCandidates !== [] && $queueAvailability['field']) {
                    $this->executeFirstFieldCandidate($account, $village, $session, $fieldCandidates);
                }

                if ($buildingCandidate !== null && $queueAvailability['building']) {
                    $this->executeBuildingCandidate($account, $village, $session, $buildingCandidate);
                }

                return;
            }

            if ($buildingCandidate !== null && $queueAvailability['building']) {
                $buildingWasExecuted = $this->executeBuildingCandidate($account, $village, $session, $buildingCandidate);

                if ($buildingWasExecuted) {
                    return;
                }
            }

            if ($fieldCandidates !== [] && $queueAvailability['field']) {
                $this->executeFirstFieldCandidate($account, $village, $session, $fieldCandidates);
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
        $priorityMap = $this->normalizeFieldPriority($settings->field_priority);
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

        return array_values($candidates);
    }

    /**
     * Prevent lower-priority fields from running too far ahead of higher-priority ones.
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
        $minimumHigherPriorityLevel = null;

        foreach ($fieldSlots as $fieldSlot) {
            if (! $fieldSlot instanceof VillageBuilding) {
                continue;
            }

            $fieldKey = TravianBuildingCatalog::fieldKeyForGid((int) $fieldSlot->building_gid);

            if ($fieldKey === null) {
                continue;
            }

            if (($priorityMap[$fieldKey] ?? 999) >= $candidatePriority) {
                continue;
            }

            $fieldLevel = (int) $fieldSlot->current_level;
            $minimumHigherPriorityLevel = $minimumHigherPriorityLevel === null
                ? $fieldLevel
                : min($minimumHigherPriorityLevel, $fieldLevel);
        }

        if ($minimumHigherPriorityLevel === null) {
            return true;
        }

        return ((int) $candidateSlot->current_level + 1) <= ($minimumHigherPriorityLevel + 1);
    }

    /**
     * Try field candidates in order until one can actually issue a build action.
     *
     * @param  list<array{slot: VillageBuilding, field_key: string}>  $candidates
     */
    protected function executeFirstFieldCandidate(Account $account, Village $village, AccountSession $session, array $candidates): bool
    {
        foreach ($candidates as $candidate) {
            if ($this->executeFieldCandidate($account, $village, $session, $candidate)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Select the next building target that can be upgraded or constructed.
     *
     * @return array{
     *     target: VillageBuildingTarget,
     *     current_slot: VillageBuilding,
     *     target_gid: int,
     *     mode: 'upgrade'|'construct'
     * }|null
     */
    protected function selectBuildingCandidate(Village $village): ?array
    {
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

            $currentSlot = $village->buildings->firstWhere('slot_id', (int) $target->slot_id);

            if (! $currentSlot instanceof VillageBuilding) {
                continue;
            }

            $currentGid = (int) $currentSlot->building_gid;
            $currentLevel = (int) $currentSlot->current_level;

            if ($currentGid !== 0 && $currentGid !== $targetGid) {
                continue;
            }

            if ($currentGid === 0) {
                return [
                    'target' => $target,
                    'current_slot' => $currentSlot,
                    'target_gid' => $targetGid,
                    'mode' => 'construct',
                ];
            }

            if ($currentLevel >= $targetLevel) {
                continue;
            }

            return [
                'target' => $target,
                'current_slot' => $currentSlot,
                'target_gid' => $targetGid,
                'mode' => 'upgrade',
            ];
        }

        return null;
    }

    /**
     * Execute a field upgrade candidate.
     *
     * @param  array{slot: VillageBuilding, field_key: string}  $candidate
     */
    protected function executeFieldCandidate(Account $account, Village $village, AccountSession $session, array $candidate): bool
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
            return false;
        }

        $this->performBuildAction(
            account: $account,
            village: $village,
            session: $session,
            actionUri: $resolvedAction['action_uri'],
            payload: [
                'queue_kind' => 'field',
                'slot_id' => (int) $slot->slot_id,
                'building_gid' => (int) $slot->building_gid,
                'building_name' => $slot->building_type,
                'current_level' => (int) $slot->current_level,
                'target_level' => (int) $slot->current_level + 1,
                'build_page_uri' => $buildPageUri,
                'build_effective_uri' => $resolvedAction['build_effective_uri'],
                'field_key' => $candidate['field_key'],
            ],
            successMessage: 'Field upgrade order issued successfully.',
        );

        return true;
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
    protected function executeBuildingCandidate(Account $account, Village $village, AccountSession $session, array $candidate): bool
    {
        $target = $candidate['target'];
        $currentSlot = $candidate['current_slot'];
        $targetGid = $candidate['target_gid'];
        $buildPageUri = (string) config('travian.paths.build', '/build.php')
            .'?id='.(int) $target->slot_id;

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
            $this->absoluteUri((string) config('travian.paths.village_center', '/dorf2.php'), $account),
            $candidate['mode'] === 'construct' ? $targetGid : null,
        );

        if ($resolvedAction === null) {
            return false;
        }

        $this->performBuildAction(
            account: $account,
            village: $village,
            session: $session,
            actionUri: $resolvedAction['action_uri'],
            payload: [
                'queue_kind' => 'building',
                'slot_id' => (int) $target->slot_id,
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
            ],
            successMessage: $candidate['mode'] === 'construct'
                ? 'Building construction order issued successfully.'
                : 'Building upgrade order issued successfully.',
        );

        return true;
    }

    /**
     * Resolve a clickable construction action URI from a build page.
     *
     * @return array{action_uri: string, build_effective_uri: string}|null
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

        $actionUri = $this->extractActionUriFromBuildPage($response->body, $targetGid);

        if ($actionUri === null) {
            return null;
        }

        return [
            'action_uri' => $actionUri,
            'build_effective_uri' => $response->effectiveUri,
        ];
    }

    /**
     * Parse the first usable non-gold build action from a build page.
     */
    protected function extractActionUriFromBuildPage(string $html, ?int $targetGid = null): ?string
    {
        $xpath = $this->createXPath($html);
        $buttonNodes = [];

        if ($targetGid !== null) {
            $targetWrapper = $xpath->query("//*[@id='contract_building{$targetGid}']")?->item(0);

            if ($targetWrapper instanceof DOMElement) {
                $buttonNodes = iterator_to_array($xpath->query('.//button[@onclick]', $targetWrapper) ?: []);
            }
        }

        if ($buttonNodes === []) {
            $buttonNodes = iterator_to_array($xpath->query('//button[@onclick]') ?: []);
        }

        foreach ($buttonNodes as $buttonNode) {
            if (! $buttonNode instanceof DOMElement) {
                continue;
            }

            $onclick = html_entity_decode((string) $buttonNode->getAttribute('onclick'), ENT_QUOTES | ENT_HTML5, 'UTF-8');

            if (! str_contains($onclick, 'action=build')) {
                continue;
            }

            if (preg_match("/window\\.location\\.href\\s*=\\s*'([^']+)'/", $onclick, $matches) !== 1) {
                continue;
            }

            $actionUri = html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');

            if (str_contains($actionUri, 'buildmaster')) {
                continue;
            }

            return $actionUri;
        }

        return null;
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

    /**
     * Create an XPath instance for the provided HTML string.
     */
    protected function createXPath(string $html): DOMXPath
    {
        $document = new DOMDocument;

        libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="utf-8" ?>'.$html);
        libxml_clear_errors();

        return new DOMXPath($document);
    }
}
