<?php

namespace App\Application\Accounts\Troops;

use App\Application\Accounts\Session\Contracts\AccountSession;
use App\Application\Accounts\Troops\Data\ParsedResearchPage;
use App\Application\Accounts\Troops\Data\ParsedTroopQueueEntry;
use App\Application\Accounts\Troops\Data\RefreshedVillageTroopState;
use App\Application\Accounts\Troops\Parsers\AcademyPageParser;
use App\Application\Accounts\Troops\Parsers\SmithyPageParser;
use App\Application\Accounts\Troops\Parsers\TrainingBuildingPageParser;
use App\Application\Travian\TravianTroopCatalog;
use App\Models\Account;
use App\Models\Village;

class RefreshVillageTroopSnapshot
{
    public function __construct(
        protected TrainingBuildingPageParser $trainingBuildingPageParser,
        protected AcademyPageParser $academyPageParser,
        protected SmithyPageParser $smithyPageParser,
    ) {}

    public function handle(Account $account, Village $village, AccountSession $session): RefreshedVillageTroopState
    {
        $village->loadMissing('runtimeState', 'buildings');
        $tribeId = $village->runtimeState?->tribe_id !== null ? (int) $village->runtimeState->tribe_id : null;
        $units = $this->initialUnits($tribeId, $village->troopSnapshot?->units ?? []);
        $trainingPages = [];
        $trainingQueues = [];
        $pages = [];

        foreach ([19, 20] as $buildingGid) {
            $slot = $this->buildingSlot($village, $buildingGid);

            if ($slot === null) {
                $pages['training:'.$buildingGid] = ['status' => 'missing_building'];

                foreach ($units as &$unitState) {
                    if ((int) data_get($unitState, 'training.building_gid') === $buildingGid) {
                        $unitState['training']['available'] = false;
                    }
                }
                unset($unitState);

                continue;
            }

            $response = $session->get($this->buildUri($slot, $buildingGid), $this->documentRequestOptions());
            $parsedPage = $this->trainingBuildingPageParser->parse($response->body);

            if ($response->statusCode !== 200 || $parsedPage->actionUri === null) {
                $pages['training:'.$buildingGid] = [
                    'status' => 'invalid_response',
                    'slot_id' => $slot,
                    'status_code' => $response->statusCode,
                ];

                continue;
            }

            $trainingPages[$buildingGid] = ['page' => $parsedPage, 'effective_uri' => $response->effectiveUri];
            $trainingQueues[(string) $buildingGid] = $this->serializeQueue($parsedPage->queue);
            $pages['training:'.$buildingGid] = ['status' => 'observed', 'slot_id' => $slot, 'status_code' => $response->statusCode];

            foreach ($units as &$unitState) {
                if ((int) data_get($unitState, 'training.building_gid') === $buildingGid) {
                    $unitState['training']['available'] = false;
                }
            }
            unset($unitState);

            foreach ($parsedPage->units as $trainingUnit) {
                if (! isset($units[(string) $trainingUnit->unitId])) {
                    continue;
                }

                $units[(string) $trainingUnit->unitId]['research_state'] = 'researched';
                $units[(string) $trainingUnit->unitId]['training'] = [
                    'available' => true,
                    'building_gid' => $buildingGid,
                    'smithy_level' => $trainingUnit->smithyLevel,
                    'max_trainable' => $trainingUnit->maxTrainable,
                    'cost' => $trainingUnit->cost,
                    'crop_upkeep' => $trainingUnit->cropUpkeep,
                    'duration_seconds' => $trainingUnit->durationSeconds,
                    'server_message' => $trainingUnit->serverMessage,
                ];
            }
        }

        $pages['training:21'] = ['status' => 'unsupported_pending_sample'];

        [$academyPage, $academyEffectiveUri] = $this->readResearchPage($session, $village, 22, $pages);

        if ($academyPage instanceof ParsedResearchPage) {
            $academyIsBusy = $academyPage->queue !== [];

            foreach ($academyPage->units as $researchUnit) {
                if (! isset($units[(string) $researchUnit->unitId])) {
                    continue;
                }

                $hasMissingRequirement = collect($researchUnit->requirements)->contains(
                    static fn (array $requirement): bool => ! $requirement['met'],
                );
                $units[(string) $researchUnit->unitId]['research_state'] = match (true) {
                    $researchUnit->actionUri !== null => 'available',
                    $hasMissingRequirement => 'blocked_requirements',
                    $academyIsBusy => 'academy_busy',
                    $researchUnit->serverMessage !== null => 'blocked_resources',
                    default => 'unavailable',
                };
                $units[(string) $researchUnit->unitId]['research'] = [
                    'cost' => $researchUnit->cost,
                    'duration_seconds' => $researchUnit->durationSeconds,
                    'requirements' => $researchUnit->requirements,
                    'server_message' => $researchUnit->serverMessage,
                ];
            }

            foreach ($academyPage->queue as $queueEntry) {
                if (isset($units[(string) $queueEntry->unitId])) {
                    $units[(string) $queueEntry->unitId]['research_state'] = 'in_progress';
                }
            }
        }

        [$smithyPage, $smithyEffectiveUri] = $this->readResearchPage($session, $village, 13, $pages);

        if (! $smithyPage instanceof ParsedResearchPage && data_get($pages, 'smithy.status') === 'missing_building') {
            foreach ($units as &$unitState) {
                $unitState['smithy']['available'] = false;
                $unitState['smithy']['current_level'] = null;
                $unitState['smithy']['actionable'] = false;
            }
            unset($unitState);
        }

        if ($smithyPage instanceof ParsedResearchPage) {
            foreach ($smithyPage->units as $smithyUnit) {
                if (! isset($units[(string) $smithyUnit->unitId])) {
                    continue;
                }

                $units[(string) $smithyUnit->unitId]['research_state'] = 'researched';
                $units[(string) $smithyUnit->unitId]['smithy'] = [
                    'available' => true,
                    'current_level' => $smithyUnit->currentLevel ?? 0,
                    'next_cost' => $smithyUnit->cost,
                    'duration_seconds' => $smithyUnit->durationSeconds,
                    'actionable' => $smithyUnit->actionUri !== null,
                    'resource_shortage' => $smithyUnit->hasResourceShortage,
                    'server_message' => $smithyUnit->serverMessage,
                ];
            }
        }

        $snapshot = $village->troopSnapshot()->updateOrCreate([], [
            'units' => $units,
            'training_queues' => $trainingQueues,
            'research_queue' => $this->serializeQueue($academyPage?->queue ?? []),
            'smithy_queue' => $this->serializeQueue($smithyPage?->queue ?? []),
            'pages' => $pages,
            'server_reported_at' => now(),
        ]);

        return new RefreshedVillageTroopState(
            snapshot: $snapshot,
            trainingPages: $trainingPages,
            academyPage: $academyPage,
            academyEffectiveUri: $academyEffectiveUri,
            smithyPage: $smithyPage,
            smithyEffectiveUri: $smithyEffectiveUri,
        );
    }

    /** @param array<string, mixed> $previousUnits @return array<string, array<string, mixed>> */
    protected function initialUnits(?int $tribeId, array $previousUnits = []): array
    {
        $units = [];

        foreach (TravianTroopCatalog::definitionsForTribe($tribeId) as $definition) {
            $default = [
                'research_state' => $definition['initially_unlocked'] ? 'researched' : 'unknown',
                'research' => null,
                'training' => [
                    'available' => false,
                    'building_gid' => $definition['training_building_gid'],
                ],
                'smithy' => [
                    'available' => false,
                    'current_level' => null,
                ],
            ];
            $previous = $previousUnits[(string) $definition['unit_id']] ?? null;
            $units[(string) $definition['unit_id']] = is_array($previous)
                ? array_replace_recursive($default, $previous)
                : $default;
        }

        return $units;
    }

    /** @param array<string, array<string, mixed>> $pages @return array{ParsedResearchPage|null, string|null} */
    protected function readResearchPage(AccountSession $session, Village $village, int $buildingGid, array &$pages): array
    {
        $slot = $this->buildingSlot($village, $buildingGid);
        $pageKey = $buildingGid === 22 ? 'academy' : 'smithy';

        if ($slot === null) {
            $pages[$pageKey] = ['status' => 'missing_building'];

            return [null, null];
        }

        $response = $session->get($this->buildUri($slot, $buildingGid), $this->documentRequestOptions());
        $parsedPage = $buildingGid === 22
            ? $this->academyPageParser->parse($response->body)
            : $this->smithyPageParser->parse($response->body);
        $pages[$pageKey] = ['status' => 'observed', 'slot_id' => $slot, 'status_code' => $response->statusCode];

        return [$parsedPage, $response->effectiveUri];
    }

    protected function buildingSlot(Village $village, int $buildingGid): ?int
    {
        $building = $village->buildings
            ->first(fn ($building): bool => (int) $building->building_gid === $buildingGid
                && (int) $building->slot_id >= 19
                && (int) $building->slot_id <= 40);

        return $building === null ? null : (int) $building->slot_id;
    }

    protected function buildUri(int $slot, int $buildingGid): string
    {
        return (string) config('travian.paths.build', '/build.php').'?id='.$slot.'&gid='.$buildingGid;
    }

    /** @param list<ParsedTroopQueueEntry> $queue @return list<array<string, int|string|null>> */
    protected function serializeQueue(array $queue): array
    {
        return array_map(static fn (ParsedTroopQueueEntry $entry): array => [
            'unit_id' => $entry->unitId,
            'quantity' => $entry->quantity,
            'remaining_seconds' => $entry->remainingSeconds,
            'target_level' => $entry->targetLevel,
            'recorded_at' => now()->toIso8601String(),
        ], $queue);
    }

    /** @return array<string, mixed> */
    protected function documentRequestOptions(?string $referer = null): array
    {
        $headers = [
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Cache-Control' => 'no-cache',
            'Pragma' => 'no-cache',
        ];

        if ($referer !== null && $referer !== '') {
            $headers['Referer'] = $referer;
        }

        return [
            'headers' => $headers,
            'allow_redirects' => ['max' => 5, 'strict' => false, 'referer' => true],
        ];
    }
}
