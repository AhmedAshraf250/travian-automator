<?php

use App\Application\Accounts\Construction\ExecuteVillageConstruction;
use App\Application\Accounts\Session\Contracts\AccountSession;
use App\Application\Accounts\Session\Data\SessionResponse;
use App\Application\Accounts\Sync\Data\ParsedConstructionQueueEntry;
use App\Application\Accounts\Sync\Data\ParsedDorf1Overview;
use App\Application\Accounts\Sync\Data\ParsedDorf2Overview;
use App\Application\Accounts\Sync\Data\ParsedVillageResourceState;
use App\Application\Accounts\Sync\Data\ParsedVillageRuntimeState;
use App\Application\Accounts\Sync\Data\ParsedVillageSlot;
use App\Application\Accounts\Sync\Data\ParsedVillageSummary;
use App\Application\Accounts\Sync\Parsers\Dorf1OverviewParser;
use App\Application\Accounts\Sync\Parsers\Dorf2OverviewParser;
use App\Enums\ActivityType;
use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\VillageSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function bindConstructionRefreshSnapshots(array $dorf1Overviews, array $dorf2Overviews): void
{
    app()->instance(Dorf1OverviewParser::class, new class($dorf1Overviews) extends Dorf1OverviewParser
    {
        /**
         * @param  list<ParsedDorf1Overview>  $overviews
         */
        public function __construct(protected array $overviews) {}

        public function parse(string $html): ParsedDorf1Overview
        {
            if (count($this->overviews) > 1) {
                /** @var ParsedDorf1Overview $overview */
                $overview = array_shift($this->overviews);

                return $overview;
            }

            return $this->overviews[0];
        }
    });

    app()->instance(Dorf2OverviewParser::class, new class($dorf2Overviews) extends Dorf2OverviewParser
    {
        /**
         * @param  list<ParsedDorf2Overview>  $overviews
         */
        public function __construct(protected array $overviews) {}

        public function parse(string $html): ParsedDorf2Overview
        {
            if (count($this->overviews) > 1) {
                /** @var ParsedDorf2Overview $overview */
                $overview = array_shift($this->overviews);

                return $overview;
            }

            return $this->overviews[0];
        }
    });
}

/**
 * @param  list<array{
 *     building_name: string,
 *     target_level: int,
 *     remaining_seconds: int,
 *     remaining_label: string|null,
 *     finish_label: string|null
 * }>  $constructionEntries
 * @param  list<array{
 *     slot_id: int,
 *     building_gid: int,
 *     building_name: string|null,
 *     current_level: int
 * }>  $fieldSlots
 */
function fakeDorf1Overview(
    string $villageId,
    string $villageName,
    int $tribeId,
    array $constructionEntries = [],
    array $fieldSlots = [],
): ParsedDorf1Overview {
    $queueEntries = array_map(
        static fn (array $entry): ParsedConstructionQueueEntry => new ParsedConstructionQueueEntry(
            buildingName: $entry['building_name'],
            targetLevel: $entry['target_level'],
            remainingSeconds: $entry['remaining_seconds'],
            remainingLabel: $entry['remaining_label'],
            finishLabel: $entry['finish_label'],
        ),
        $constructionEntries,
    );

    $parsedFieldSlots = array_map(
        static fn (array $slot): ParsedVillageSlot => new ParsedVillageSlot(
            slotId: $slot['slot_id'],
            buildingGid: $slot['building_gid'],
            buildingName: $slot['building_name'],
            currentLevel: $slot['current_level'],
            kind: 'field',
        ),
        $fieldSlots,
    );

    $summary = new ParsedVillageSummary(
        travianVillageId: $villageId,
        name: $villageName,
        x: 9,
        y: 60,
        population: 89,
        isActive: true,
        switchUri: '/dorf1.php?newdid='.$villageId,
    );

    return new ParsedDorf1Overview(
        activeVillage: $summary,
        resourceState: new ParsedVillageResourceState(
            wood: 1800,
            clay: 1700,
            iron: 1600,
            crop: 1500,
            woodProduction: 140,
            clayProduction: 120,
            ironProduction: 104,
            cropProduction: 37,
            freeCropProduction: 0,
            warehouseCapacity: 4000,
            granaryCapacity: 1700,
        ),
        runtimeState: new ParsedVillageRuntimeState(
            tribeId: $tribeId,
            troopSlots: array_fill(0, 11, 0),
            incomingAttackCount: 0,
            incomingReinforcementCount: 0,
            outgoingMovementCount: 0,
            movementEntries: [],
            constructionEntries: $queueEntries,
            heroStatus: null,
            heroRemainingSeconds: null,
        ),
        villages: [$summary],
        fieldSlots: $parsedFieldSlots,
        constructionQueue: $queueEntries,
    );
}

/**
 * @param  list<array{
 *     slot_id: int,
 *     building_gid: int,
 *     building_name: string|null,
 *     current_level: int,
 *     is_empty?: bool
 * }>  $buildingSlots
 */
function fakeDorf2Overview(array $buildingSlots = []): ParsedDorf2Overview
{
    return new ParsedDorf2Overview(
        buildingSlots: array_map(
            static fn (array $slot): ParsedVillageSlot => new ParsedVillageSlot(
                slotId: $slot['slot_id'],
                buildingGid: $slot['building_gid'],
                buildingName: $slot['building_name'],
                currentLevel: $slot['current_level'],
                kind: 'building',
                isEmpty: (bool) ($slot['is_empty'] ?? false),
            ),
            $buildingSlots,
        ),
    );
}

test('roman village can issue one field order and one building order in the same pass', function () {
    bindConstructionRefreshSnapshots(
        [
            fakeDorf1Overview('23378', 'قرية Marshal25', 1, [
                [
                    'building_name' => 'الحطاب',
                    'target_level' => 2,
                    'remaining_seconds' => 300,
                    'remaining_label' => '0:05:00',
                    'finish_label' => '12:30',
                ],
            ], [
                ['slot_id' => 1, 'building_gid' => 1, 'building_name' => 'الحطاب', 'current_level' => 1],
            ]),
            fakeDorf1Overview('23378', 'قرية Marshal25', 1, [
                [
                    'building_name' => 'المبنى الرئيسي',
                    'target_level' => 11,
                    'remaining_seconds' => 720,
                    'remaining_label' => '0:12:00',
                    'finish_label' => '12:37',
                ],
                [
                    'building_name' => 'الحطاب',
                    'target_level' => 2,
                    'remaining_seconds' => 295,
                    'remaining_label' => '0:04:55',
                    'finish_label' => '12:30',
                ],
            ], [
                ['slot_id' => 1, 'building_gid' => 1, 'building_name' => 'الحطاب', 'current_level' => 1],
            ]),
        ],
        [
            fakeDorf2Overview([
                ['slot_id' => 26, 'building_gid' => 15, 'building_name' => 'المبنى الرئيسي', 'current_level' => 10],
            ]),
            fakeDorf2Overview([
                ['slot_id' => 26, 'building_gid' => 15, 'building_name' => 'المبنى الرئيسي', 'current_level' => 10],
            ]),
        ],
    );

    $account = Account::factory()->create([
        'server_url' => 'https://example.com/',
    ]);

    $village = $account->villages()->create([
        'travian_village_id' => '23378',
        'name' => 'قرية Marshal25',
        'is_active' => true,
    ]);

    $village->settings()->create([
        'field_priority' => VillageSetting::defaultFieldPriority(),
        'pause_fields' => false,
        'pause_buildings' => false,
    ]);

    $village->runtimeState()->create([
        'tribe_id' => 1,
        'troop_slots' => [],
        'movement_entries' => [],
        'construction_entries' => [],
        'server_reported_at' => now(),
    ]);

    $village->buildings()->create([
        'slot_id' => 1,
        'building_gid' => 1,
        'building_type' => 'الحطاب',
        'current_level' => 1,
    ]);

    $village->buildings()->create([
        'slot_id' => 26,
        'building_gid' => 15,
        'building_type' => 'المبنى الرئيسي',
        'current_level' => 10,
    ]);

    $village->buildingTargets()->create([
        'slot_id' => 26,
        'building_gid' => 15,
        'building_type' => 'المبنى الرئيسي',
        'target_level' => 11,
        'priority' => 1,
        'is_enabled' => true,
    ]);

    $session = new class implements AccountSession
    {
        /** @var list<string> */
        public array $requests = [];

        /** @var array<string, array<string, mixed>> */
        public array $requestOptions = [];

        public function get(string $uri, array $options = []): SessionResponse
        {
            $this->requests[] = $uri;
            $this->requestOptions[$uri] = $options;

            return match ($uri) {
                '/dorf1.php?newdid=23378' => $this->response('<body class="village1"></body>', 'https://example.com/dorf1.php?newdid=23378'),
                '/build.php?id=1' => $this->response('<button onclick="this.disabled = true; window.location.href = \'/dorf1.php?id=1&amp;gid=1&amp;action=build&amp;checksum=field001\'; return false;"></button>', 'https://example.com/build.php?id=1&gid=1'),
                '/dorf1.php?id=1&gid=1&action=build&checksum=field001' => $this->response('<body class="village1"></body>', 'https://example.com/dorf1.php?id=1&gid=1&action=build&checksum=field001'),
                '/build.php?id=26&gid=15' => $this->response('<button onclick="this.disabled = true; window.location.href = \'/dorf2.php?id=26&amp;gid=15&amp;action=build&amp;checksum=build015\'; return false;"></button>', 'https://example.com/build.php?id=26&gid=15'),
                '/dorf2.php?id=26&gid=15&action=build&checksum=build015' => $this->response('<body class="village2"></body>', 'https://example.com/dorf2.php?id=26&gid=15&action=build&checksum=build015'),
                '/dorf1.php' => $this->response('<body class="village1"></body>', 'https://example.com/dorf1.php'),
                '/dorf2.php' => $this->response('<body class="village2"></body>', 'https://example.com/dorf2.php'),
                default => $this->response('<body class="village1"></body>', 'https://example.com'.$uri),
            };
        }

        public function postForm(string $uri, array $formParams, array $options = []): SessionResponse
        {
            throw new RuntimeException('postForm was not expected during construction execution.');
        }

        public function postJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            throw new RuntimeException('postJson was not expected during construction execution.');
        }

        public function persist(): void {}

        protected function response(string $body, string $effectiveUri): SessionResponse
        {
            return new SessionResponse(
                statusCode: 200,
                body: $body,
                effectiveUri: $effectiveUri,
                headers: [],
            );
        }
    };

    $loadedVillage = $village->fresh(['settings', 'runtimeState', 'buildings', 'buildingTargets']);

    app(ExecuteVillageConstruction::class)->handle($account->fresh(), $loadedVillage, $session);

    $finalVillage = $village->fresh();
    $fieldLog = ActivityLog::query()->where('message', 'Field upgrade order issued successfully.')->latest('id')->first();
    $buildingLog = ActivityLog::query()->where('message', 'Building upgrade order issued successfully.')->latest('id')->first();

    expect(ActivityLog::query()->where('activity_type', ActivityType::Build)->where('status', 'done')->count())->toBe(2);
    expect(ActivityLog::query()->where('message', 'Field upgrade order issued successfully.')->exists())->toBeTrue();
    expect(ActivityLog::query()->where('message', 'Building upgrade order issued successfully.')->exists())->toBeTrue();
    expect($session->requests)->toContain('/dorf1.php?newdid=23378');
    expect($session->requests)->toContain('/dorf1.php?id=1&gid=1&action=build&checksum=field001');
    expect($session->requests)->toContain('/dorf2.php?id=26&gid=15&action=build&checksum=build015');
    expect($session->requests)->toContain('/build.php?id=1');
    expect($session->requests)->not->toContain('/build.php?id=1&gid=1');
    expect($session->requestOptions['/build.php?id=1']['headers']['Referer'] ?? null)->toBe('https://example.com/dorf1.php');
    expect($session->requestOptions['/dorf1.php?id=1&gid=1&action=build&checksum=field001']['headers']['Referer'] ?? null)->toBe('https://example.com/build.php?id=1&gid=1');
    expect($session->requestOptions['/build.php?id=26&gid=15']['headers']['Referer'] ?? null)->toBe('https://example.com/dorf2.php');
    expect($fieldLog?->result['refresh_strategy'] ?? null)->toBe('action_response_dorf1');
    expect($fieldLog?->result['dorf2_effective_uri'] ?? null)->toBeNull();
    expect($buildingLog?->result['refresh_strategy'] ?? null)->toBe('action_response_dorf2_plus_dorf1');
    expect($buildingLog?->payload['target_level'] ?? null)->toBe(11);
    expect($buildingLog?->payload['final_target_level'] ?? null)->toBe(11);
    expect($finalVillage->runtimeState?->construction_entries)->toHaveCount(2);
    expect($finalVillage->runtimeState?->construction_entries[0]['building_name'] ?? null)->toBe('المبنى الرئيسي');
    expect($finalVillage->runtimeState?->construction_entries[0]['target_level'] ?? null)->toBe(11);
    expect($finalVillage->runtimeState?->construction_entries[1]['building_name'] ?? null)->toBe('الحطاب');
    expect($finalVillage->runtimeState?->construction_entries[1]['target_level'] ?? null)->toBe(2);
    expect($buildingLog?->payload['remaining_label'] ?? null)->toBe('0:12:00');
    expect($buildingLog?->result['overview_refreshed'] ?? null)->toBeTrue();
});

test('non roman village prioritizes the explicit building target when the shared queue is free', function () {
    bindConstructionRefreshSnapshots(
        [
            fakeDorf1Overview('23379', 'قرية Support', 2, [
                [
                    'building_name' => 'المبنى الرئيسي',
                    'target_level' => 6,
                    'remaining_seconds' => 600,
                    'remaining_label' => '0:10:00',
                    'finish_label' => '12:40',
                ],
            ], [
                ['slot_id' => 1, 'building_gid' => 1, 'building_name' => 'الحطاب', 'current_level' => 1],
            ]),
        ],
        [
            fakeDorf2Overview([
                ['slot_id' => 26, 'building_gid' => 15, 'building_name' => 'المبنى الرئيسي', 'current_level' => 5],
            ]),
        ],
    );

    $account = Account::factory()->create([
        'server_url' => 'https://example.com/',
    ]);

    $village = $account->villages()->create([
        'travian_village_id' => '23379',
        'name' => 'قرية Support',
        'is_active' => true,
    ]);

    $village->settings()->create([
        'field_priority' => VillageSetting::defaultFieldPriority(),
        'pause_fields' => false,
        'pause_buildings' => false,
    ]);

    $village->runtimeState()->create([
        'tribe_id' => 2,
        'troop_slots' => [],
        'movement_entries' => [],
        'construction_entries' => [],
        'server_reported_at' => now(),
    ]);

    $village->buildings()->create([
        'slot_id' => 1,
        'building_gid' => 1,
        'building_type' => 'الحطاب',
        'current_level' => 1,
    ]);

    $village->buildings()->create([
        'slot_id' => 26,
        'building_gid' => 15,
        'building_type' => 'المبنى الرئيسي',
        'current_level' => 5,
    ]);

    $village->buildingTargets()->create([
        'slot_id' => 26,
        'building_gid' => 15,
        'building_type' => 'المبنى الرئيسي',
        'target_level' => 8,
        'priority' => 1,
        'is_enabled' => true,
    ]);

    $session = new class implements AccountSession
    {
        /** @var list<string> */
        public array $requests = [];

        public function get(string $uri, array $options = []): SessionResponse
        {
            $this->requests[] = $uri;

            return match ($uri) {
                '/dorf1.php?newdid=23379' => $this->response('<body class="village1"></body>', 'https://example.com/dorf1.php?newdid=23379'),
                '/build.php?id=26&gid=15' => $this->response('<button onclick="this.disabled = true; window.location.href = \'/dorf2.php?id=26&amp;gid=15&amp;action=build&amp;checksum=build206\'; return false;"></button>', 'https://example.com/build.php?id=26&gid=15'),
                '/dorf2.php?id=26&gid=15&action=build&checksum=build206' => $this->response('<body class="village2"></body>', 'https://example.com/dorf2.php?id=26&gid=15&action=build&checksum=build206'),
                '/dorf1.php' => $this->response('<body class="village1"></body>', 'https://example.com/dorf1.php'),
                '/dorf2.php' => $this->response('<body class="village2"></body>', 'https://example.com/dorf2.php'),
                default => $this->response('<body class="village1"></body>', 'https://example.com'.$uri),
            };
        }

        public function postForm(string $uri, array $formParams, array $options = []): SessionResponse
        {
            throw new RuntimeException('postForm was not expected during construction execution.');
        }

        public function postJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            throw new RuntimeException('postJson was not expected during construction execution.');
        }

        public function persist(): void {}

        protected function response(string $body, string $effectiveUri): SessionResponse
        {
            return new SessionResponse(
                statusCode: 200,
                body: $body,
                effectiveUri: $effectiveUri,
                headers: [],
            );
        }
    };

    $loadedVillage = $village->fresh(['settings', 'runtimeState', 'buildings', 'buildingTargets']);

    app(ExecuteVillageConstruction::class)->handle($account->fresh(), $loadedVillage, $session);

    expect(ActivityLog::query()->where('activity_type', ActivityType::Build)->where('status', 'done')->count())->toBe(1);
    expect(ActivityLog::query()->where('message', 'Building upgrade order issued successfully.')->exists())->toBeTrue();
    $buildingLog = ActivityLog::query()->where('message', 'Building upgrade order issued successfully.')->latest('id')->first();

    expect($buildingLog?->payload['target_level'] ?? null)->toBe(6);
    expect($buildingLog?->payload['final_target_level'] ?? null)->toBe(8);
    expect($session->requests)->toContain('/build.php?id=26&gid=15');
    expect($session->requests)->not->toContain('/build.php?id=1&gid=1');
    expect($village->fresh()->runtimeState?->construction_entries)->toHaveCount(1);
    expect($village->fresh()->runtimeState?->construction_entries[0]['building_name'] ?? null)->toBe('المبنى الرئيسي');
    expect($village->fresh()->runtimeState?->construction_entries[0]['target_level'] ?? null)->toBe(6);
    expect($village->fresh()->runtimeState?->construction_entries[0]['remaining_label'] ?? null)->toBe('0:10:00');
});

test('roman village can still issue a field order while the building queue is already occupied', function () {
    bindConstructionRefreshSnapshots(
        [
            fakeDorf1Overview('23380', 'قرية Roman', 1, [
                [
                    'building_name' => 'المبنى الرئيسي',
                    'target_level' => 11,
                    'remaining_seconds' => 180,
                    'remaining_label' => '0:03:00',
                    'finish_label' => '12:45',
                ],
                [
                    'building_name' => 'الحطاب',
                    'target_level' => 2,
                    'remaining_seconds' => 300,
                    'remaining_label' => '0:05:00',
                    'finish_label' => '12:47',
                ],
            ], [
                ['slot_id' => 1, 'building_gid' => 1, 'building_name' => 'الحطاب', 'current_level' => 1],
            ]),
        ],
        [
            fakeDorf2Overview([
                ['slot_id' => 26, 'building_gid' => 15, 'building_name' => 'المبنى الرئيسي', 'current_level' => 10],
            ]),
        ],
    );

    $account = Account::factory()->create([
        'server_url' => 'https://example.com/',
    ]);

    $village = $account->villages()->create([
        'travian_village_id' => '23380',
        'name' => 'قرية Roman',
        'is_active' => true,
    ]);

    $village->settings()->create([
        'field_priority' => VillageSetting::defaultFieldPriority(),
        'pause_fields' => false,
        'pause_buildings' => false,
    ]);

    $village->runtimeState()->create([
        'tribe_id' => 1,
        'troop_slots' => [],
        'movement_entries' => [],
        'construction_entries' => [
            [
                'building_name' => 'المبنى الرئيسي',
                'target_level' => 11,
                'remaining_seconds' => 180,
                'remaining_label' => '0:03:00',
                'finish_label' => '12:45',
            ],
        ],
        'server_reported_at' => now(),
    ]);

    $village->buildings()->create([
        'slot_id' => 1,
        'building_gid' => 1,
        'building_type' => 'الحطاب',
        'current_level' => 1,
    ]);

    $village->buildings()->create([
        'slot_id' => 26,
        'building_gid' => 15,
        'building_type' => 'المبنى الرئيسي',
        'current_level' => 10,
    ]);

    $village->buildingTargets()->create([
        'slot_id' => 26,
        'building_gid' => 15,
        'building_type' => 'المبنى الرئيسي',
        'target_level' => 11,
        'priority' => 1,
        'is_enabled' => true,
    ]);

    $session = new class implements AccountSession
    {
        /** @var list<string> */
        public array $requests = [];

        public function get(string $uri, array $options = []): SessionResponse
        {
            $this->requests[] = $uri;

            return match ($uri) {
                '/dorf1.php?newdid=23380' => $this->response('<body class="village1"></body>', 'https://example.com/dorf1.php?newdid=23380'),
                '/build.php?id=1' => $this->response('<button onclick="this.disabled = true; window.location.href = \'/dorf1.php?id=1&amp;gid=1&amp;action=build&amp;checksum=field101\'; return false;"></button>', 'https://example.com/build.php?id=1&gid=1'),
                '/dorf1.php?id=1&gid=1&action=build&checksum=field101' => $this->response('<body class="village1"></body>', 'https://example.com/dorf1.php?id=1&gid=1&action=build&checksum=field101'),
                '/dorf1.php' => $this->response('<body class="village1"></body>', 'https://example.com/dorf1.php'),
                '/dorf2.php' => $this->response('<body class="village2"></body>', 'https://example.com/dorf2.php'),
                default => $this->response('<body class="village1"></body>', 'https://example.com'.$uri),
            };
        }

        public function postForm(string $uri, array $formParams, array $options = []): SessionResponse
        {
            throw new RuntimeException('postForm was not expected during construction execution.');
        }

        public function postJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            throw new RuntimeException('postJson was not expected during construction execution.');
        }

        public function persist(): void {}

        protected function response(string $body, string $effectiveUri): SessionResponse
        {
            return new SessionResponse(
                statusCode: 200,
                body: $body,
                effectiveUri: $effectiveUri,
                headers: [],
            );
        }
    };

    $loadedVillage = $village->fresh(['settings', 'runtimeState', 'buildings', 'buildingTargets']);

    app(ExecuteVillageConstruction::class)->handle($account->fresh(), $loadedVillage, $session);

    expect(ActivityLog::query()->where('activity_type', ActivityType::Build)->where('status', 'done')->count())->toBe(1);
    expect(ActivityLog::query()->where('message', 'Field upgrade order issued successfully.')->exists())->toBeTrue();
    expect($session->requests)->toContain('/build.php?id=1');
    expect($session->requests)->not->toContain('/build.php?id=26&gid=15');
    expect($village->fresh()->runtimeState?->construction_entries)->toHaveCount(2);
});

test('field automation can fall back to a lower priority field when the first choice is currently unavailable', function () {
    bindConstructionRefreshSnapshots(
        [
            fakeDorf1Overview('23381', 'قرية Flexible', 2, [
                [
                    'building_name' => 'حفرة الطين',
                    'target_level' => 4,
                    'remaining_seconds' => 120,
                    'remaining_label' => '0:02:00',
                    'finish_label' => '12:25',
                ],
            ], [
                ['slot_id' => 1, 'building_gid' => 1, 'building_name' => 'الحطاب', 'current_level' => 3],
                ['slot_id' => 2, 'building_gid' => 2, 'building_name' => 'حفرة الطين', 'current_level' => 3],
            ]),
        ],
        [
            fakeDorf2Overview(),
        ],
    );

    $account = Account::factory()->create([
        'server_url' => 'https://example.com/',
    ]);

    $village = $account->villages()->create([
        'travian_village_id' => '23381',
        'name' => 'قرية Flexible',
        'is_active' => true,
    ]);

    $village->settings()->create([
        'field_priority' => [
            'wood' => 1,
            'clay' => 2,
            'iron' => 3,
            'crop' => 4,
        ],
        'pause_fields' => false,
        'pause_buildings' => true,
    ]);

    $village->runtimeState()->create([
        'tribe_id' => 2,
        'troop_slots' => [],
        'movement_entries' => [],
        'construction_entries' => [],
        'server_reported_at' => now(),
    ]);

    $village->buildings()->create([
        'slot_id' => 1,
        'building_gid' => 1,
        'building_type' => 'الحطاب',
        'current_level' => 3,
    ]);

    $village->buildings()->create([
        'slot_id' => 2,
        'building_gid' => 2,
        'building_type' => 'حفرة الطين',
        'current_level' => 3,
    ]);

    $session = new class implements AccountSession
    {
        /** @var list<string> */
        public array $requests = [];

        public function get(string $uri, array $options = []): SessionResponse
        {
            $this->requests[] = $uri;

            return match ($uri) {
                '/dorf1.php?newdid=23381' => $this->response('<body class="village1"></body>', 'https://example.com/dorf1.php?newdid=23381'),
                '/build.php?id=1' => $this->response('<div class="contract">No available build action</div>', 'https://example.com/build.php?id=1&gid=1'),
                '/build.php?id=2' => $this->response('<button onclick="this.disabled = true; window.location.href = \'/dorf1.php?id=2&amp;gid=2&amp;action=build&amp;checksum=clay204\'; return false;"></button>', 'https://example.com/build.php?id=2&gid=2'),
                '/dorf1.php?id=2&gid=2&action=build&checksum=clay204' => $this->response('<body class="village1"></body>', 'https://example.com/dorf1.php?id=2&gid=2&action=build&checksum=clay204'),
                '/dorf1.php' => $this->response('<body class="village1"></body>', 'https://example.com/dorf1.php'),
                '/dorf2.php' => $this->response('<body class="village2"></body>', 'https://example.com/dorf2.php'),
                default => $this->response('<body class="village1"></body>', 'https://example.com'.$uri),
            };
        }

        public function postForm(string $uri, array $formParams, array $options = []): SessionResponse
        {
            throw new RuntimeException('postForm was not expected during construction execution.');
        }

        public function postJson(string $uri, array $payload, array $options = []): SessionResponse
        {
            throw new RuntimeException('postJson was not expected during construction execution.');
        }

        public function persist(): void {}

        protected function response(string $body, string $effectiveUri): SessionResponse
        {
            return new SessionResponse(
                statusCode: 200,
                body: $body,
                effectiveUri: $effectiveUri,
                headers: [],
            );
        }
    };

    $loadedVillage = $village->fresh(['settings', 'runtimeState', 'buildings', 'buildingTargets']);

    app(ExecuteVillageConstruction::class)->handle($account->fresh(), $loadedVillage, $session);

    $buildLog = ActivityLog::query()->where('message', 'Field upgrade order issued successfully.')->latest('id')->first();

    expect($session->requests)->toContain('/build.php?id=1');
    expect($session->requests)->toContain('/build.php?id=2');
    expect($session->requests)->toContain('/dorf1.php?id=2&gid=2&action=build&checksum=clay204');
    expect($buildLog?->payload['field_key'] ?? null)->toBe('clay');
    expect($buildLog?->payload['building_name'] ?? null)->toBe('حفرة الطين');
    expect($buildLog?->payload['remaining_label'] ?? null)->toBe('0:02:00');
    expect($village->fresh()->runtimeState?->construction_entries[0]['building_name'] ?? null)->toBe('حفرة الطين');
    expect($village->fresh()->runtimeState?->construction_entries[0]['target_level'] ?? null)->toBe(4);
});
